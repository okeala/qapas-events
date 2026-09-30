<?php
namespace App\Models;
use Illuminate\Support\Facades\{DB,Validator};
use Illuminate\Validation\ValidationException;
class CupPlan extends Record {
 protected $attributes=['quantity'=>1000,'village_pool'=>500,'independent_pool'=>500,'capacity_ml'=>300,'received_quantity'=>0,'suggested_deposit_cents'=>250,'reference_quantity'=>250,'reference_net_cents'=>35639,'reference_gross_cents'=>42410,'hot_food_status'=>'unverified'];
 public function scenario(){return $this->belongsTo(Scenario::class);}
 public function allocations(){return $this->hasMany(CupAllocation::class);}
 public function save(array $options=[]){return DB::transaction(function()use($options){if($this->exists)self::whereKey($this->id)->lockForUpdate()->firstOrFail();return parent::save($options);},3);}
 public function report(): array {
  $buy=$this->scenario->budgetLines()->where('costing_key','cups-purchase')->first();$sale=$this->scenario->budgetLines()->where('costing_key','cups-resale')->first();
  $cost=$buy?\App\Domain\Finance\Pricing::forecast($buy,$buy->forecast_quantity):null;$revenue=$sale?\App\Domain\Finance\Pricing::forecast($sale,$sale->forecast_quantity):null;
  return ['cost_cents'=>$cost,'resale_cents'=>$revenue,'stock_margin_cents'=>$cost!==null&&$revenue!==null?$revenue-$cost:null,'issued'=>$this->allocations()->sum('issued_quantity'),'returned'=>$this->allocations()->sum('returned_quantity'),'available'=>$this->received_quantity-$this->allocations()->sum('issued_quantity')+$this->allocations()->sum('returned_quantity'),'quantity_aligned'=>$sale&&$sale->forecast_quantity===$this->quantity];
 }
 protected static function booted(): void {parent::booted();static::saving(function(self $p){
  Validator::make($p->getAttributes(),['quantity'=>'required|integer|between:1,1000000','village_pool'=>'required|integer|min:0','independent_pool'=>'required|integer|min:0','received_quantity'=>'required|integer|min:0|lte:quantity','suggested_deposit_cents'=>'required|integer|between:0,100000','capacity_ml'=>'required|integer|between:1,10000','hot_food_status'=>'in:unverified,confirmed,not_suitable'])->validate();
  if($p->quantity!=$p->village_pool+$p->independent_pool)throw ValidationException::withMessages(['quantity'=>'Les deux lots doivent totaliser le stock prévu.']);
  if($p->exists&&$p->isDirty('scenario_id'))throw ValidationException::withMessages(['scenario_id'=>'Conserver le scénario du stock.']);
  if($p->received_quantity>0&&blank($p->receipt_evidence))throw ValidationException::withMessages(['receipt_evidence'=>'Documenter la réception physique.']);
  if($p->hot_food_status==='confirmed'&&blank($p->hot_food_evidence))throw ValidationException::withMessages(['hot_food_evidence'=>'Déclaration du fabricant pour cette référence, températures, durée et usages exacts requis.']);
  if($p->exists){foreach(['village','independent'] as $kind)if($p->allocations()->whereHas('stand',fn($q)=>$q->where('kind',$kind))->sum('planned_quantity')>$p->{$kind.'_pool'})throw ValidationException::withMessages([$kind.'_pool'=>'Lot inférieur aux allocations prévues.']);if($p->received_quantity<$p->allocations()->sum('issued_quantity')-$p->allocations()->sum('returned_quantity'))throw ValidationException::withMessages(['received_quantity'=>'Réception inférieure au stock distribué net.']);}
 });}
}
