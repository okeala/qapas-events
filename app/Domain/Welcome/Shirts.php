<?php
namespace App\Domain\Welcome;
use App\Models\WelcomePackPlan;
use App\Domain\Finance\{Money,Pricing};
final class Shirts {
 public function calculate(WelcomePackPlan $plan): array {
  $sizes=array_fill_keys(WelcomePackPlan::SIZES,0);$missing=[];$people=0;
  if($plan->use_roster){
   foreach($plan->people??[] as $person){$people++;if(empty($person['size'])||empty($person['confirmed']))$missing[]='Taille à confirmer : '.$person['name'];if(isset($sizes[$person['size']??'']))$sizes[$person['size']]++;}
   $keys=array_column($plan->people??[],'person_key');foreach($plan->electedPeople() as $person)if(!in_array($person['person_key'],$keys,true))$missing[]='Joueur élu absent du relevé : '.$person['name'];
  }else{
   foreach($plan->cohorts??[] as $cohort)$people+=(int)$cohort['quantity'];
   foreach($plan->sizes??[] as $row)$sizes[$row['size']]+=(int)$row['quantity'];
   if(array_sum($sizes)!==$people)$missing[]='La répartition par taille doit correspondre aux personnes prévues.';
  }
  $base=$people*$plan->shirts_per_person;$reserve=(int)ceil($base*$plan->reserve_percent/100);$total=$base+$reserve;
  // Largest remainder allocation: one global ceiling, never a ceiling for every size.
  $allocation=[];$fractions=[];$distributed=0;$known=array_sum($sizes);
  foreach($sizes as $size=>$count){$share=$known?$reserve*$count/$known:0;$extra=(int)floor($share);$allocation[$size]=['people'=>$count,'base'=>$count*$plan->shirts_per_person,'reserve'=>$extra,'total'=>$count*$plan->shirts_per_person+$extra];$fractions[$size]=$share-$extra;$distributed+=$extra;}
  arsort($fractions,SORT_NUMERIC);foreach(array_keys($fractions) as $size){if($distributed>=$reserve||!$known)break;$allocation[$size]['reserve']++;$allocation[$size]['total']++;$distributed++;}
  if(!$people)$missing[]='Aucun bénéficiaire défini.';
  $cost=0;$costComplete=true;$scenario=null;
  foreach(['shirtCostLine'=>$total,'setupCostLine'=>1,'deliveryCostLine'=>1] as $relation=>$quantity){$line=$plan->$relation;
   if(!$line||$line->superseded_by_id||$line->kind!=='cost'||$line->scenario->event_project_id!==$plan->event_project_id||$line->scenario->is_archived||$line->forecast_quantity!==$quantity||$line->unit_gross_cents===null){$costComplete=false;continue;}
   if($scenario&&$scenario!==$line->scenario_id)$costComplete=false;$scenario=$line->scenario_id;$cost+=$line->unit_gross_cents*$quantity;
   if(Pricing::pending($line)||$line->vat_basis_points===null||blank($line->price_source))$costComplete=false;
  }
  $sponsor=$plan->sponsor;$line=$sponsor?->budgetLine;$received=0;
  if($sponsor?->agreed()&&$sponsor->scope==='welcome_pack'&&$sponsor->purpose==='shirts'&&$line&&$line->scenario_id===$scenario&&$line->kind==='revenue'&&$line->scope==='structural'&&!$line->superseded_by_id&&$line->verified()&&$line->vat_basis_points!==null)$received=min(Money::net($sponsor->cash_pledged_cents??0,$line->vat_basis_points),Money::net($line->unit_gross_cents,$line->vat_basis_points)*$line->paid_quantity);
  return ['people'=>$people,'base'=>$base,'reserve'=>$reserve,'total'=>$total,'sizes'=>$allocation,'missing'=>array_values(array_unique($missing)),'sizes_ready'=>$plan->use_roster&&$people>0&&!$missing,'cost_complete'=>$costComplete,'cost_cents'=>$costComplete?$cost:null,'estimated_cost_cents'=>$cost,'received_net_cents'=>$received,'funding_gap_cents'=>$costComplete?max(0,$cost-$received):null,'sponsor_requirement_cents'=>$costComplete?$cost:null];
 }
}
