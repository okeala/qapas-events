<?php
namespace App\Models;
use Illuminate\Support\Facades\{DB,Validator};
use Illuminate\Validation\ValidationException;
class CupAllocation extends Record {
 protected $attributes=['planned_quantity'=>0,'issued_quantity'=>0,'returned_quantity'=>0];
 public function cupPlan(){return $this->belongsTo(CupPlan::class);}
 public function stand(){return $this->belongsTo(Stand::class);}
 public function save(array $options=[]){return DB::transaction(function()use($options){$this->setRelation('cupPlan',CupPlan::whereKey($this->cup_plan_id)->lockForUpdate()->firstOrFail());return parent::save($options);},3);}
 protected static function booted(): void {parent::booted();static::saving(function(self $a){
  Validator::make($a->getAttributes(),['planned_quantity'=>'required|integer|between:0,1000000','issued_quantity'=>'required|integer|min:0|lte:planned_quantity','returned_quantity'=>'required|integer|min:0|lte:issued_quantity'])->validate();$p=$a->cupPlan;$stand=Stand::find($a->stand_id);
  if(!$stand||!in_array($stand->kind,['village','independent'],true)||!$p->scenario->includedStands()->where('stands.id',$stand->id)->exists())throw ValidationException::withMessages(['stand_id'=>'Choisir un stand participant à ce scénario.']);
  if($a->exists&&$a->isDirty(['stand_id','cup_plan_id']))throw ValidationException::withMessages(['stand_id'=>'Conserver le stand et le plan de cette allocation.']);
  $others=$p->allocations()->when($a->exists,fn($q)=>$q->where('id','!=',$a->id));
  if((clone $others)->whereHas('stand',fn($q)=>$q->where('kind',$stand->kind))->sum('planned_quantity')+$a->planned_quantity>$p->{$stand->kind.'_pool'})throw ValidationException::withMessages(['planned_quantity'=>'Le lot de cette catégorie serait dépassé.']);
  if((clone $others)->sum('issued_quantity')-(clone $others)->sum('returned_quantity')+$a->issued_quantity-$a->returned_quantity>$p->received_quantity)throw ValidationException::withMessages(['issued_quantity'=>'Stock physique reçu insuffisant.']);
  if($a->issued_quantity>0&&(blank($a->responsible)||blank($a->handover_evidence)))throw ValidationException::withMessages(['handover_evidence'=>'Responsable et preuve de remise requis.']);
 });}
}
