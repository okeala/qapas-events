<?php
namespace App\Models;
use Illuminate\Support\Facades\Validator;
class CommercialPlan extends Record {
 protected $attributes=['independent_price_cents'=>65000,'village_price_cents'=>50000,'vat_basis_points'=>2300,'unknown_allowance_cents'=>200000,'rounding_cents'=>5000];
 protected function casts(): array {return ['applied_at'=>'datetime'];}
 public function scenario(){return $this->belongsTo(Scenario::class);}
 public function report(): array {return app(\App\Domain\Finance\CommercialPricing::class)->report($this);}
 protected static function booted(): void {parent::booted();static::saving(function(self $p){Validator::make($p->getAttributes(),['village_price_cents'=>'required|integer|between:5,100000000|multiple_of:5','independent_price_cents'=>'required|integer|between:0,100000000','vat_basis_points'=>'required|integer|between:0,10000','unknown_allowance_cents'=>'required|integer|between:0,100000000','rounding_cents'=>'required|integer|between:1,1000000'])->validate();if($p->exists&&$p->isDirty('scenario_id'))throw \Illuminate\Validation\ValidationException::withMessages(['scenario_id'=>'Conserver le scénario.']);if($p->exists&&$p->isDirty(['village_price_cents','independent_price_cents','vat_basis_points','unknown_allowance_cents','rounding_cents']))$p->applied_at=null;});}
}
