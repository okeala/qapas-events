<?php
namespace App\Domain\Finance;
use App\Models\Scenario;
final class LaunchReport {
 public function calculate(Scenario $s,array $base): array {
  $r=['missing'=>[],'stands'=>[],'site_cost_cents'=>0,'site_gross_cents'=>0,'verified_net_cents'=>0,'verified_gross_cents'=>0,'advance_due_cents'=>0,'prepaid_margin_cents'=>0,'prepaid_cash_cents'=>0,'launch_ready'=>false,'expansion_ready'=>false,'tent_estimate'=>null];
  if($s->eventProject->prize_policy_version)$r['missing']=array_merge($r['missing'],app(\App\Domain\Awards\Forquilha::class)->report($s->eventProject,$s)['missing']);
  $pack=\App\Models\WelcomePackPlan::where('event_project_id',$s->event_project_id)->first();if($pack){$wr=$pack->report();if(!$wr['cost_complete']||$pack->shirtCostLine?->scenario_id!==$s->id)$r['missing'][]='Welcome pack : effectifs, quantité budgétée, devis complet et IVA à valider dans ce scénario';foreach($wr['missing'] as $missing)$r['missing'][]='Welcome pack : '.$missing;}
  $costNet=$s->organizer_full_monthly_cents*$s->months;$costGross=$costNet;
  foreach($s->budgetLines as $l){
   $gross=($l->unit_gross_cents??0);$net=$l->vat_basis_points===null?($l->kind==='cost'?$gross:0):Money::net($gross,$l->vat_basis_points);
   if($l->kind==='cost'){$costNet+=($l->deductible?$net:$gross)*$l->forecast_quantity;$costGross+=$gross*$l->forecast_quantity;if($l->paid_by==='organizer')$r['advance_due_cents']+=$gross*$l->paid_quantity-$l->reimbursed_cents;}
   if($l->kind==='revenue'&&!in_array($l->scope,['bar','fries','soup'],true)&&$l->verified()){$r['verified_net_cents']+=$net*$l->paid_quantity;$r['verified_gross_cents']+=$gross*$l->paid_quantity;}
   if($l->stand_id&&!$s->includedStands->contains('id',$l->stand_id))$r['missing'][]='Ligne rattachée à un stand absent du scénario : '.$l->name;
  }
  foreach($s->includedActivities as $a){if($a->track==='official'&&$a->sponsorships()->exists()&&!$a->sponsorships()->get()->contains(fn($sponsor)=>$sponsor->agreed()))$r['missing'][]=$a->name.' : sponsor d’épreuve à contractualiser';foreach($a->materials->whereNotNull('shared_cost_key') as $material)if(!$s->budgetLines->contains(fn($line)=>$line->costing_key===$material->shared_cost_key&&$line->kind==='cost'&&$line->forecast_quantity>0))$r['missing'][]=$a->name.' : coût mutualisé absent du scénario ('.$material->shared_cost_key.')';$c=$a->costReport();$costNet+=$c['economic_cents'];$costGross+=$c['gross_cents'];}
  foreach($s->includedFeatures as $f){
   if($f->event_project_id!==$s->event_project_id){$r['missing'][]='Équipement hors édition';continue;}
   if(!$f->needs_complete)$r['missing'][]=$f->name.' : besoins à confirmer';
   if($f->needs->isEmpty())$r['missing'][]=$f->name.' : besoin ou mise à disposition à documenter';
   foreach($f->needs as $n){
    if($n->quantity===null||$n->unit_gross_cents===null||($n->basis==='per_day'&&!$s->event_days)){$r['missing'][]=$f->name.' / '.$n->name.' : chiffrage incomplet';continue;}
    if(Pricing::pending($n)||$n->vat_basis_points===null)$r['missing'][]=$n->name.' : prix ou IVA à valider';
    if($n->unit_gross_cents===0&&blank($n->evidence))$r['missing'][]=$n->name.' : gratuité à justifier';
    $count=$n->quantity*($n->basis==='per_day'?$s->event_days:1);$gross=$n->unit_gross_cents*$count;$net=($n->deductible&&$n->vat_basis_points!==null?Money::net($n->unit_gross_cents,$n->vat_basis_points):$n->unit_gross_cents)*$count;
    $r['site_cost_cents']+=$net;$r['site_gross_cents']+=$gross;
   }
  }
  $costNet+=$r['site_cost_cents'];$costGross+=$r['site_gross_cents'];
  foreach($s->includedStands as $stand){
   if($s->eventProject->community_version&&$stand->kind==='village'&&!\App\Models\FreguesiaAgreement::where('stand_id',$stand->id)->where('status','signed')->exists())$r['missing'][]=$stand->name.' : convention de la freguesia à faire signer par son représentant habilité';
   foreach($stand->requirements as $need)if(!$need->valid())$r['missing'][]=$stand->name.' : besoin technique à valider · '.$need->name;
   if($s->furniture_paid_by_participant&&!$stand->furnitureBudget()['confirmed'])$r['missing'][]=$stand->name.' : mobilier à charge participant, location/apport à confirmer';
   if($stand->event_project_id!==$s->event_project_id){$r['missing'][]='Stand hors édition';continue;}
   $contribution=['name'=>$stand->name,'forecast_cents'=>0,'committed_cents'=>0,'verified_cents'=>0,'complete'=>$stand->direct_costs_complete];
   $lines=$s->budgetLines->where('stand_id',$stand->id);
   if(!$stand->direct_costs_complete||!$lines->contains('kind','cost')||!$lines->contains('kind','revenue')){$r['missing'][]=$stand->name.' : revenus et coûts directs à recenser';$contribution['complete']=false;}
   foreach($lines as $l){
    if(!in_array($l->kind,['cost','revenue'])||($l->forecast_quantity===0&&$l->committed_quantity===0&&$l->paid_quantity===0))continue;
    if(Pricing::pending($l)||$l->vat_basis_points===null)$contribution['complete']=false;
    if($l->unit_gross_cents===null||($l->kind==='revenue'&&$l->vat_basis_points===null)){$contribution['complete']=false;continue;}
    $net=$l->vat_basis_points===null?$l->unit_gross_cents:Money::net($l->unit_gross_cents,$l->vat_basis_points);
    if($l->kind==='cost'){$v=($l->deductible?$net:$l->unit_gross_cents)*$l->forecast_quantity;foreach(['forecast_cents','committed_cents','verified_cents'] as $key)$contribution[$key]-=$v;}
    else{$contribution['forecast_cents']+=$net*$l->forecast_quantity;$contribution['committed_cents']+=$net*$l->committed_quantity;$contribution['verified_cents']+=$l->verified()?$net*$l->paid_quantity:0;}
   }
   $r['stands'][]=$contribution;
   if($s->launch_model&&$stand->kind==='village'&&!$stand->partners()->where('main_slot',1)->where('status','active')->exists())$r['missing'][]=$stand->name.' : parrain principal à activer';
  }
  if($s->launch_model){
   if(!$s->event_days)$r['missing'][]='Nombre de jours à fixer';
   if($s->includedStands->where('kind','village')->count()!==$s->team_target||$s->includedStands->where('kind','independent')->count()!==$s->independent_target||$s->includedStands->count()!==$s->stand_target)$r['missing'][]='Les stands sélectionnés ne correspondent pas au format annoncé';
   if($s->shelter_model==='distributed'){
    $r['tent_estimate']=$s->includedStands->sum('shelter_target');foreach($s->includedStands as $stand)if(!$stand->hospitality_validated||blank($stand->hospitality_evidence)||$stand->shelter_source==='undecided'||$stand->sheltered_capacity<$stand->shelter_target||$stand->seated_capacity<(int)ceil($stand->shelter_target/2))$r['missing'][]=$stand->name.' : abri, moitié des personnes assises, espaces debout et circulations à valider';
   }else{
   if(!$s->guests_per_stand||!$s->tent_capacity)$r['missing'][]='Hypothèse de fréquentation et capacité du chapiteau à définir';
   else{$r['tent_estimate']=$s->stand_target*$s->guests_per_stand;if($s->tent_capacity<$r['tent_estimate'])$r['missing'][]='Chapiteau inférieur à l’hypothèse du scénario (hors validation du site)';}
   }
   if($s->contingency_cents===null||$s->refund_reserve_cents===null||blank($s->reserve_evidence))$r['missing'][]='Imprévus et exposition aux remboursements à documenter';
   for($day=1;$day<=($s->event_days??0);$day++){
    $slots=$s->programSlots->where('day_number',$day);$valid=$slots->filter(fn($slot)=>$slot->activity?->track==='official'&&$slot->activity->status!=='archived'&&$s->includedActivities->contains('id',$slot->activity_id));
    if($valid->pluck('activity_id')->unique()->count()<$s->minimum_daily_activities)$r['missing'][]='Jour '.$day.' : programme officiel insuffisant ou non chiffré';
   }
  }
  $r['prepaid_margin_cents']=$r['verified_net_cents']-$costNet-($s->contingency_cents??0);
  // Conservative: output VAT reserved; input VAT credits are not cash available for suppliers.
  $r['prepaid_cash_cents']=$r['verified_net_cents']-$costGross-($s->contingency_cents??0)-($s->refund_reserve_cents??0);
  return $r;
 }
}
