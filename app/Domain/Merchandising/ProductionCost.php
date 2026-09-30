<?php
namespace App\Domain\Merchandising;
use App\Models\MerchandisingOption;
final class ProductionCost {
 public function report(MerchandisingOption $option): array {
  $plan=$option->welcomePackPlan;$qty=$plan->report()['total']+$option->prototype_quantity;$variable=0;$missing=[];
  foreach($option->costs??[] as $cost){if(($cost['unit_cents']??null)===null){$missing[]=$cost['name'];continue;}$variable+=(int)$cost['unit_cents']*(int)$cost['per_piece'];}
  foreach(['minutes_per_piece','hourly_cost_cents','machine_cost_cents','other_fixed_cents','opportunity_hourly_cents'] as $field)if($option->$field===null)$missing[]=$field;
  $minutes=$option->setup_minutes+$qty*($option->minutes_per_piece??0);$labor=(int)ceil($minutes*($option->hourly_cost_cents??0)/60);
  $cash=$qty*$variable+$labor+($option->machine_cost_cents??0)+($option->other_fixed_cents??0);$opportunity=(int)ceil($option->qapas_minutes*($option->opportunity_hourly_cents??0)/60);
  $internalUnit=$variable+(int)ceil(($option->minutes_per_piece??0)*($option->hourly_cost_cents??0)/60);$saving=$option->external_unit_cents!==null?$option->external_unit_cents-$internalUnit:null;
  $investment=($option->machine_cost_cents??0)+($option->other_fixed_cents??0)+(int)ceil($option->setup_minutes*($option->hourly_cost_cents??0)/60);
  return ['quantity'=>$qty,'event_quantity'=>$plan->report()['total'],'prototypes'=>$option->prototype_quantity,'minutes'=>$minutes,'cash_cents'=>$cash,'opportunity_cents'=>$opportunity,'economic_cents'=>$cash+$opportunity,'missing'=>$missing,'complete'=>!$missing,'saving_per_piece_cents'=>$saving,'machine_payback_quantity'=>$saving!==null&&$saving>0?(int)ceil(max(0,$investment-($option->external_fixed_cents??0))/$saving):null];
 }
}
