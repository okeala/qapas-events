<?php
namespace App\Models;
use Illuminate\Support\Facades\Validator;
class MerchandisingOption extends Record {
 public const METHODS=['print'=>'Impression sous-traitée','patches'=>'Écussons sous-traités + couture locale','direct'=>'Broderie directe sous-traitée','inhouse'=>'Machine QAPAS + opératrice'];
 protected $attributes=['prototype_quantity'=>2,'setup_minutes'=>0,'qapas_minutes'=>0];
 protected function casts(): array {return ['costs'=>'array','sample_approved_at'=>'datetime'];}
 public function welcomePackPlan(){return $this->belongsTo(WelcomePackPlan::class);}
 public function report(): array {return app(\App\Domain\Merchandising\ProductionCost::class)->report($this);}
 protected static function booted(): void {parent::booted();static::saving(function(self $o){
  Validator::make(array_merge($o->getAttributes(),['costs'=>$o->costs]),['method'=>'required|in:'.implode(',',array_keys(self::METHODS)),'costs'=>'required|array|min:1|max:20','costs.*.name'=>'required|string|max:150','costs.*.per_piece'=>'required|integer|between:1,10','costs.*.unit_cents'=>'nullable|integer|between:0,10000000','prototype_quantity'=>'integer|between:0,100','setup_minutes'=>'integer|between:0,100000','qapas_minutes'=>'integer|between:0,100000']+array_fill_keys(['minutes_per_piece','hourly_cost_cents','machine_cost_cents','other_fixed_cents','external_unit_cents','external_fixed_cents','opportunity_hourly_cents'],'nullable|integer|between:0,100000000'))->validate();
  if($o->exists&&$o->isDirty(['welcome_pack_plan_id','method']))throw \Illuminate\Validation\ValidationException::withMessages(['method'=>'Conserver le lot et la méthode.']);
  if($o->exists&&$o->isDirty(['costs','specification','prototype_quantity'])){$o->sample_approved_at=null;$o->sample_evidence=null;}
  if($o->sample_approved_at&&($o->sample_approved_at->isFuture()||blank($o->sample_evidence)))throw \Illuminate\Validation\ValidationException::withMessages(['sample_evidence'=>'Échantillon réel, lavage, rendu photo et accord sur le motif à documenter.']);
 });}
}
