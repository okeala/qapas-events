<?php
namespace App\Models;
use Illuminate\Support\Facades\Validator;
class ActivityMaterial extends Record {
 protected $attributes=['basis'=>'fixed','procurement'=>'purchase','unit'=>'pièce','deductible'=>false];
 protected function casts(): array {return ['deductible'=>'boolean'];}
 public function activity(){return $this->belongsTo(Activity::class);}
 protected static function booted(): void {
  parent::booted();static::saving(function(ActivityMaterial $m){\App\Domain\Finance\Pricing::validate($m);Validator::make($m->getAttributes(),[
   'quantity'=>'nullable|integer|min:1|max:1000000','unit_gross_cents'=>'nullable|integer|min:0|max:100000000',
   'vat_basis_points'=>'nullable|integer|min:0|max:10000','basis'=>'in:fixed,per_run','procurement'=>'in:purchase,rental,loan,sponsor',
  ])->validate();if($m->activity_location_id&&!ActivityLocation::whereKey($m->activity_location_id)->where('activity_id',$m->activity_id)->exists())throw \Illuminate\Validation\ValidationException::withMessages(['activity_location_id'=>'L’implantation doit appartenir à cette épreuve.']);});
 }
}
