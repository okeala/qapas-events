<?php
namespace App\Domain\Finance;
use App\Models\FurniturePlan;
final class FurnitureCost {
 public function calculate(FurniturePlan $p): array {
  $volume=0;$surface=0;foreach($p->parts??[] as $part){$n=(int)$part['quantity'];$l=$part['length_mm']/1000;$w=$part['width_mm']/1000;$t=$part['thickness_mm']/1000;$volume+=$n*$l*$w*$t;$surface+=$n*2*($l*$w+$l*$t+$w*$t);}
  $missing=[];foreach(['wood_price_cents_m3','coverage_m2_litre','coats','hardware_cents_unit','roller_cents_batch','other_cents_batch','work_minutes_unit','hourly_cents','service_cents_unit','vat_basis_points'] as $key)if($p->$key===null)$missing[]=$key;
  if(!$p->parts)$missing[]='parts';$q=max(1,$p->quantity);$purchaseVolume=$volume*(1+$p->waste_basis_points/10000);
  $wood=$p->wood_price_cents_m3===null?null:(int)ceil($purchaseVolume*$p->wood_price_cents_m3);
  $litres=$p->coverage_m2_litre&&$p->coats?$surface*$p->coats/(float)$p->coverage_m2_litre:null;
  $cans=$litres===null?null:(int)ceil($litres*$q/(float)$p->can_litres);
  $cash=($wood===null||$cans===null||$p->hardware_cents_unit===null||$p->roller_cents_batch===null||$p->other_cents_batch===null)?null:($wood+$p->hardware_cents_unit)*$q+$cans*$p->can_price_cents+$p->roller_cents_batch+$p->other_cents_batch;
  $labour=$p->work_minutes_unit===null||$p->hourly_cents===null?null:(int)ceil($p->work_minutes_unit*$p->hourly_cents/60);
  $unit=$cash===null||$labour===null?null:(int)ceil($cash/$q)+$labour;$rent=[];
  foreach(array_unique([3000,5000,(int)$p->rental_gross_cents]) as $price){$net=$p->vat_basis_points===null?null:Money::net($price,$p->vat_basis_points);$margin=$net===null||$p->service_cents_unit===null?null:$net-$p->service_cents_unit;$rent[]=['gross_cents'=>$price,'net_cents'=>$net,'contribution_cents'=>$margin,'rotations'=>$unit!==null&&$margin>0?(int)ceil($unit/$margin):null];}
  return ['net_volume_m3'=>$volume,'purchase_volume_m3'=>$purchaseVolume,'surface_m2'=>$surface,'litres_unit'=>$litres,'cans_batch'=>$cans,'wood_cents_unit'=>$wood,'cash_batch_cents'=>$cash,'labour_cents_unit'=>$labour,'full_unit_cents'=>$unit,'rentals'=>$rent,'missing'=>$missing,'complete'=>!$missing,'verified'=>!$missing&&$p->inputs_verified&&filled($p->evidence)];
 }
}
