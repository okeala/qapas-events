<?php
namespace App\Models;
use Illuminate\Support\Facades\Validator;
class CateringService extends Record {
 protected $attributes=['chef_confirmed'=>false];
 protected function casts(): array {return ['chef_confirmed'=>'boolean'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 protected static function booted(): void {parent::booted();static::saving(function(self $s){Validator::make($s->getAttributes(),['planned_portions'=>'nullable|integer|between:0,100000','prepared_portions'=>'nullable|integer|between:0,100000','sold_portions'=>'nullable|integer|between:0,100000','price_gross_cents'=>'nullable|integer|between:0,1000000'])->validate();
  if($s->sold_portions!==null&&($s->prepared_portions===null||$s->sold_portions>$s->prepared_portions))throw \Illuminate\Validation\ValidationException::withMessages(['sold_portions'=>'Renseigner la production réelle ; les ventes ne peuvent la dépasser.']);
  if($s->chef_confirmed&&blank($s->evidence))throw \Illuminate\Validation\ValidationException::withMessages(['evidence'=>'Documenter l’accord du chef.']);
 });}
}
