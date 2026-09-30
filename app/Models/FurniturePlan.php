<?php
namespace App\Models;
use Illuminate\Support\Facades\Validator;
class FurniturePlan extends Record {
 protected $attributes=['quantity'=>12,'waste_basis_points'=>1500,'can_price_cents'=>4000,'can_litres'=>5,'rental_gross_cents'=>5000,'inputs_verified'=>false];
 protected function casts(): array {return ['parts'=>'array','inputs_verified'=>'boolean','can_litres'=>'float','coverage_m2_litre'=>'float'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function report(): array {return app(\App\Domain\Finance\FurnitureCost::class)->calculate($this);}
 public static function illustrativeParts(): array {return [
  ['name'=>'Plateau + deux assises (hypothèse)','quantity'=>9,'length_mm'=>1800,'width_mm'=>145,'thickness_mm'=>32],
  ['name'=>'Pieds avant découpe des angles','quantity'=>4,'length_mm'=>900,'width_mm'=>95,'thickness_mm'=>45],
  ['name'=>'Traverses des assises','quantity'=>2,'length_mm'=>1550,'width_mm'=>145,'thickness_mm'=>45],
  ['name'=>'Traverses du plateau','quantity'=>2,'length_mm'=>750,'width_mm'=>95,'thickness_mm'=>45],
  ['name'=>'Contreventements','quantity'=>2,'length_mm'=>850,'width_mm'=>95,'thickness_mm'=>45],
 ];}
 protected static function booted(): void {parent::booted();static::saving(function(self $p){$rules=['parts'=>'required|array|min:1|max:50','parts.*.name'=>'required|string|max:150','parts.*.quantity'=>'required|integer|between:1,100','parts.*.length_mm'=>'required|integer|between:1,10000','parts.*.width_mm'=>'required|integer|between:1,1000','parts.*.thickness_mm'=>'required|integer|between:1,500','quantity'=>'required|integer|between:1,1000','waste_basis_points'=>'required|integer|between:0,10000','can_price_cents'=>'required|integer|between:0,1000000','can_litres'=>'required|numeric|gt:0|max:1000','coverage_m2_litre'=>'nullable|numeric|gt:0|max:100','coats'=>'nullable|integer|between:1,10','vat_basis_points'=>'nullable|integer|between:0,10000','work_minutes_unit'=>'nullable|integer|between:0,100000'];foreach(['wood_price_cents_m3','hardware_cents_unit','roller_cents_batch','other_cents_batch','hourly_cents','service_cents_unit','rental_gross_cents'] as $key)$rules[$key]='nullable|integer|between:0,100000000';Validator::make(array_merge($p->getAttributes(),['parts'=>$p->parts]),$rules)->validate();
  if($p->exists&&$p->isDirty(['parts','quantity','waste_basis_points','wood_price_cents_m3','coverage_m2_litre','coats','hardware_cents_unit','roller_cents_batch','other_cents_batch','work_minutes_unit','hourly_cents','service_cents_unit','can_price_cents','can_litres','vat_basis_points']))$p->inputs_verified=false;
  if($p->inputs_verified&&(!$p->report()['complete']||blank($p->evidence)))throw \Illuminate\Validation\ValidationException::withMessages(['evidence'=>'Renseigner tous les coûts et leur preuve avant validation.']);
 });}
}
