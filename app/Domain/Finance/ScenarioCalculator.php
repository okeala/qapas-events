<?php
namespace App\Domain\Finance;
use App\Models\Scenario;
final class ScenarioCalculator {
 public function calculate(Scenario $scenario): array {
  $result=['forecast_margin_cents'=>0,'secured_margin_cents'=>0,'paid_operating_gross_cents'=>0,'restricted_receipts_cents'=>0,'vat_reserve_cents'=>0,'missing'=>[]];
  $lines=$scenario->budgetLines;
  if (!$scenario->costs_complete) $result['missing'][]='Périmètre des coûts à confirmer';
  if (!$lines->contains('kind','cost') && $scenario->includedActivities->isEmpty()) $result['missing'][]='Coûts d’exploitation absents';
  if ($scenario->organizer_full_monthly_cents===null || $scenario->organizer_full_monthly_cents<$scenario->organizer_net_monthly_cents) $result['missing'][]='Coût complet de la rémunération et des charges à confirmer';
  $fixedPay=($scenario->organizer_full_monthly_cents??0)*$scenario->months;
  $result['forecast_margin_cents']-=$fixedPay;
  // Secured revenue must cover ALL planned costs, not merely invoices already signed.
  $result['secured_margin_cents']-=$fixedPay;
  $unpaidCosts=$fixedPay;
  foreach ($lines as $line) {
   if (!in_array($line->kind,['revenue','cost'],true)) {
    $result['restricted_receipts_cents']+=$line->unit_gross_cents*$line->paid_quantity;
    continue;
   }
   if ($line->vat_basis_points===null) {$result['missing'][]='IVA à confirmer : '.$line->name;continue;}
   $gross=(int)$line->unit_gross_cents;
   $net=Money::net($gross,(int)$line->vat_basis_points);
   if ($line->kind==='revenue') {
    $result['forecast_margin_cents']+=$net*$line->forecast_quantity;
    $result['secured_margin_cents']+=$net*$line->committed_quantity;
    $result['paid_operating_gross_cents']+=$gross*$line->paid_quantity;
    $result['vat_reserve_cents']+=($gross-$net)*$line->paid_quantity;
   } else {
    $cost=$line->deductible?$net:$gross;
    $result['forecast_margin_cents']-=$cost*$line->forecast_quantity;
    $result['secured_margin_cents']-=$cost*$line->forecast_quantity;
    $result['paid_operating_gross_cents']-=$gross*$line->paid_quantity;
    $result['vat_reserve_cents']-=($line->deductible?($gross-$net):0)*$line->paid_quantity;
    $unpaidCosts+=$gross*max(0,$line->forecast_quantity-$line->paid_quantity);
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
  $result['vat_reserve_cents']=max(0,$result['vat_reserve_cents']);
  $result['cash_after_reserves_cents']=$result['paid_operating_gross_cents']-$result['vat_reserve_cents']-$unpaidCosts;
  $result['target_cents']=$scenario->target_surplus_cents*$scenario->months;
  $result['break_even_gap_cents']=max(0,-$result['secured_margin_cents']);
  $result['target_gap_cents']=max(0,$result['target_cents']-$result['secured_margin_cents']);
  $result['complete']=count($result['missing'])===0;
  $result['break_even_secured']=$result['complete'] && $result['secured_margin_cents']>=0;
  $result['target_secured']=$result['complete'] && $result['secured_margin_cents']>=$result['target_cents'];
  return $result;
 }
}
