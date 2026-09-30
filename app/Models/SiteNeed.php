<?php
namespace App\Models;
use Illuminate\Support\Facades\Validator;
class SiteNeed extends Record {
 protected $attributes=['unit'=>'unité','basis'=>'fixed','deductible'=>false];
 protected function casts(): array {return ['deductible'=>'boolean'];}
 public function siteFeature(){return $this->belongsTo(SiteFeature::class);}
 protected static function booted(): void {parent::booted();static::saving(fn(self $n)=>Validator::make($n->getAttributes(),['quantity'=>'nullable|integer|between:1,100000','unit_gross_cents'=>'nullable|integer|between:0,100000000','vat_basis_points'=>'nullable|integer|between:0,10000','basis'=>'in:fixed,per_day'])->validate());}
}
