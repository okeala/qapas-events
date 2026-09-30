<?php
namespace App\Domain\Finance;
use App\Models\Scenario;
final class ScenarioCalculator {
 public function calculate(Scenario $scenario): array {
  $result=['forecast_margin_cents'=>0,'secured_margin_cents'=>0,'paid_operating_gross_cents'=>0,'restricted_receipts_cents'=>0,'vat_reserve_cents'=>0,'missing'=>[]];
  $lines=$scenario->budgetLines;
  if($lines->groupBy(fn($line)=>BudgetIdentity::key($line->getAttributes()))->contains(fn($group)=>$group->count()>1))$result['missing'][]='Doublons budgétaires à examiner dans ce scénario';
  if($scenario->is_archived)$result['missing'][]='Scénario archivé : ne peut pas débloquer le lancement';
  if (!$scenario->costs_complete) $result['missing'][]='Périmètre des coûts à confirmer';
  if (!$lines->contains('kind','cost') && $scenario->includedActivities->isEmpty() && $scenario->includedFeatures->isEmpty()) $result['missing'][]='Coûts d’exploitation absents';
  if ($scenario->organizer_full_monthly_cents===null || $scenario->organizer_full_monthly_cents<$scenario->organizer_net_monthly_cents) $result['missing'][]='Coût complet de la rémunération et des charges à confirmer';
  $fixedPay=($scenario->organizer_full_monthly_cents??0)*$scenario->months;
  $result['forecast_margin_cents']-=$fixedPay;
  // Secured revenue must cover ALL planned costs, not merely invoices already signed.
  $result['secured_margin_cents']-=$fixedPay;
  $unpaidCosts=$fixedPay;
  foreach ($lines as $line) {
   if($line->forecast_quantity===0&&$line->committed_quantity===0&&$line->paid_quantity===0)continue;
   if($line->unit_gross_cents===null){$result['missing'][]='Montant à chiffrer : '.$line->name;continue;}
   if (!in_array($line->kind,['revenue','cost'],true)) {
    $result['restricted_receipts_cents']+=$line->unit_gross_cents*$line->paid_quantity;
    continue;
   }
   if(Pricing::pending($line))$result['missing'][]='Prix à valider : '.$line->name;
   if ($line->vat_basis_points===null) {$result['missing'][]='IVA à confirmer : '.$line->name;if($line->kind==='revenue')continue;}
   $gross=(int)$line->unit_gross_cents;
   $net=$line->vat_basis_points===null?$gross:Money::net($gross,(int)$line->vat_basis_points);
   if ($line->kind==='revenue') {
    $result['forecast_margin_cents']+=$net*$line->forecast_quantity;
    $result['secured_margin_cents']+=($scenario->launch_model&&in_array($line->scope,['bar','fries','soup'],true))?0:$net*$line->committed_quantity;
    $result['paid_operating_gross_cents']+=$gross*$line->paid_quantity;
    $result['vat_reserve_cents']+=($gross-$net)*$line->paid_quantity;
   } else {
    $cost=$line->deductible?$net:$gross;
    $result['forecast_margin_cents']-=$cost*$line->forecast_quantity;
    $result['secured_margin_cents']-=$cost*$line->forecast_quantity;
    $result['paid_operating_gross_cents']-=$line->paid_by==='organizer'?$line->reimbursed_cents:$gross*$line->paid_quantity;
    $result['vat_reserve_cents']-=($line->deductible?($gross-$net):0)*$line->paid_quantity;
    $unpaidCosts+=$gross*max(0,$line->forecast_quantity-$line->paid_quantity);if($line->paid_by==='organizer')$unpaidCosts+=$gross*$line->paid_quantity-$line->reimbursed_cents;
   }
  }
  // Activity bills of materials are included directly, never copied as duplicate budget lines.
  $result['activity_cost_cents']=0;
  foreach($scenario->includedActivities as $activity){
   if($activity->event_project_id!==$scenario->event_project_id){$result['missing'][]='Épreuve liée à une autre édition';continue;}
   $cost=$activity->costReport();$result['activity_cost_cents']+=$cost['economic_cents'];
   $result['forecast_margin_cents']-=$cost['economic_cents'];$result['secured_margin_cents']-=$cost['economic_cents'];$unpaidCosts+=$cost['gross_cents'];
   foreach($cost['missing'] as $missing) $result['missing'][]=$activity->name.' : '.$missing;
  }
  $launch=app(LaunchReport::class)->calculate($scenario,$result);
  $result['forecast_margin_cents']-=$launch['site_cost_cents']+($scenario->contingency_cents??0);$result['secured_margin_cents']-=$launch['site_cost_cents']+($scenario->contingency_cents??0);$unpaidCosts+=$launch['site_gross_cents']+($scenario->contingency_cents??0)+($scenario->refund_reserve_cents??0);
  $result['missing']=array_merge($result['missing'],$launch['missing']);
  $result['vat_reserve_cents']=max(0,$result['vat_reserve_cents']);
  $result['cash_after_reserves_cents']=$result['paid_operating_gross_cents']-$result['vat_reserve_cents']-$unpaidCosts;
  $result['target_cents']=$scenario->target_surplus_cents*$scenario->months;
  $result['break_even_gap_cents']=max(0,-$result['secured_margin_cents']);
  $result['target_gap_cents']=max(0,$result['target_cents']-$result['secured_margin_cents']);
  $result['complete']=count($result['missing'])===0;
  $result['break_even_secured']=$result['complete'] && $result['secured_margin_cents']>=0;
  $result['target_secured']=$result['complete'] && $result['secured_margin_cents']>=$result['target_cents'];
  $launch['launch_ready']=$result['complete']&&$launch['prepaid_margin_cents']>=0&&$launch['prepaid_cash_cents']>=0;
  $launch['expansion_ready']=$launch['launch_ready']&&$launch['prepaid_margin_cents']>=$result['target_cents']&&$launch['prepaid_cash_cents']>=$result['target_cents'];
  if($scenario->parent_id){$parent=$scenario->parent?->report();if(!$parent||!$parent['expansion_ready']||$result['target_cents']<$parent['target_cents']){$launch['launch_ready']=false;$launch['expansion_ready']=false;$result['missing'][]='Palier précédent ou objectif QAPAS non préservé';$result['complete']=false;$result['target_secured']=false;$result['break_even_secured']=false;}}
  return array_merge($result,array_diff_key($launch,['missing'=>true]));
 }
}
