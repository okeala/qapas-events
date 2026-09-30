<?php
namespace App\Models;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
class StandPartner extends Record {
 protected $attributes=['status'=>'prospect'];
 public function stand(){return $this->belongsTo(Stand::class);}
 protected static function booted(): void {parent::booted();static::saving(function(self $p){
  Validator::make($p->getAttributes(),['main_slot'=>'nullable|integer|in:1','relay_slot'=>'nullable|integer|between:1,3','status'=>'in:prospect,agreed,active'])->validate();
  if(!$p->main_slot&&!$p->relay_slot)throw ValidationException::withMessages(['main_slot'=>'Choisir parrain principal et/ou une place de relais.']);
  if($p->stand?->kind!=='village')throw ValidationException::withMessages(['stand_id'=>'Le parrainage local concerne un stand de freguesia.']);
  foreach(['main_slot','relay_slot'] as $slot)if($p->$slot&&self::where('stand_id',$p->stand_id)->where($slot,$p->$slot)->when($p->exists,fn($q)=>$q->where('id','!=',$p->id))->exists())throw ValidationException::withMessages([$slot=>'Cette place est déjà attribuée.']);
  if($p->status==='active'&&(blank($p->mission)||blank($p->evidence)))throw ValidationException::withMessages(['evidence'=>'Documenter la mission et son activation.']);
 });}
}
