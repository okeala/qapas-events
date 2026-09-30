<?php
namespace App\Models;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
class StandPartner extends Record {
 protected $attributes=['status'=>'prospect'];
 protected function casts(): array {return ['meeting_candidate'=>'boolean','meeting_capacity'=>'integer','hosts_information_meeting'=>'boolean','meeting_at'=>'datetime'];}
 public function stand(){return $this->belongsTo(Stand::class);}
 protected static function booted(): void {parent::booted();static::saving(function(self $p){
  Validator::make($p->getAttributes(),['meeting_capacity'=>'nullable|integer|between:1,10000','main_slot'=>'nullable|integer|in:1','relay_slot'=>'nullable|integer|between:1,3','status'=>'in:prospect,agreed,active'])->validate();
  if(!$p->main_slot&&!$p->relay_slot)throw ValidationException::withMessages(['main_slot'=>'Choisir parrain principal et/ou une place de relais.']);
  if($p->stand?->kind!=='village')throw ValidationException::withMessages(['stand_id'=>'Le parrainage local concerne un stand de freguesia.']);
  foreach(['main_slot','relay_slot'] as $slot)if($p->$slot&&self::where('stand_id',$p->stand_id)->where($slot,$p->$slot)->when($p->exists,fn($q)=>$q->where('id','!=',$p->id))->exists())throw ValidationException::withMessages([$slot=>'Cette place est déjà attribuée.']);
  if($p->hosts_information_meeting&&$p->stand->eventProject->community_version){if(!$p->meeting_capacity||blank($p->meeting_capacity_evidence))throw ValidationException::withMessages(['meeting_capacity'=>'Capacité réellement disponible à vérifier.']);$larger=self::where('stand_id',$p->stand_id)->where('meeting_candidate',true)->whereNotNull('meeting_capacity_evidence')->where('meeting_capacity','>',$p->meeting_capacity)->exists();if($larger&&blank($p->meeting_selection_reason))throw ValidationException::withMessages(['meeting_selection_reason'=>'Un café offre davantage de place : documenter pourquoi retenir celui-ci (disponibilité, accès, date).']);}
  if($p->hosts_information_meeting&&(!$p->relay_slot||$p->status!=='active'||!$p->meeting_at||blank($p->meeting_place)||blank($p->meeting_agreement)))throw ValidationException::withMessages(['meeting_agreement'=>'Réunion : relais actif, lieu, date et accord requis.']);
  if($p->status==='active'&&(blank($p->mission)||blank($p->evidence)))throw ValidationException::withMessages(['evidence'=>'Documenter la mission et son activation.']);
 });}
}
