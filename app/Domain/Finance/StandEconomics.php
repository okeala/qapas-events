<?php
namespace App\Domain\Finance;
use App\Models\CommercialPlan;
final class StandEconomics {
 public function report(CommercialPlan $p): array {
  $s=$p->scenario;$u=app(UnitCosting::class)->calculate($s);$direct=0;$independentCosts=[];$incomplete=false;
  foreach($s->includedStands as $stand){$lines=$s->budgetLines->where('stand_id',$stand->id)->where('kind','cost')->filter(fn($l)=>$l->forecast_quantity>0);$amount=0;foreach($lines as $l){$amount+=($l->unit_gross_cents??0)*$l->forecast_quantity;if($l->unit_gross_cents===null)$incomplete=true;}$direct+=$amount;if($stand->kind==='independent')$independentCosts[]=$amount;if(!$stand->direct_costs_complete||$lines->isEmpty())$incomplete=true;}
  // Not all non-stand costs are fixed: stocks, passes and capacity steps must be reviewed separately.
  $unit=$independentCosts?max($independentCosts):null;$net=Money::net($p->independent_price_cents,$p->vat_basis_points);$contribution=$unit===null?null:$net-$unit;
  return ['stand_direct_cents'=>$direct,'other_format_cents'=>$u['gross_cost_cents']-$direct,'independent_direct_cents'=>$unit,'independent_net_cents'=>$net,'independent_contribution_cents'=>$contribution,'incomplete'=>$incomplete,'extensions'=>array_map(fn($n)=>['extra'=>$n,'total'=>$s->includedStands->count()+$n,'contribution_cents'=>$contribution===null?null:$n*$contribution],[2,4,6])];
 }
}
