<?php
namespace App\Models;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
class StandPartner extends Record {
 protected $attributes=['status'=>'prospect','share_units'=>0,'exclusive_sponsorship'=>false];
 protected function casts(): array {return ['exclusive_sponsorship'=>'boolean','share_units'=>'integer','reference_total_cents'=>'integer','is_public'=>'boolean','latitude'=>'float','longitude'=>'float','meeting_candidate'=>'boolean','meeting_capacity'=>'integer','hosts_information_meeting'=>'boolean','meeting_at'=>'datetime'];}
 public function stand(){return $this->belongsTo(Stand::class);}
 public function sponsorshipUnits(): int {return app(\App\Domain\Promotion\StandSponsoring::class)->units($this);}
 public function save(array $options=[]){return \Illuminate\Support\Facades\DB::transaction(function()use($options){$edition=Stand::whereKey($this->stand_id)->value('event_project_id');EventProject::whereKey($edition)->lockForUpdate()->firstOrFail();return parent::save($options);},3);}
 protected static function booted(): void {parent::booted();static::saving(function(self $p){
  Validator::make($p->getAttributes(),['latitude'=>'nullable|numeric|between:-90,90','longitude'=>'nullable|numeric|between:-180,180','public_address'=>'nullable|string|max:500','public_hours'=>'nullable|string|max:500','meeting_capacity'=>'nullable|integer|between:1,10000','main_slot'=>'nullable|integer|in:1','relay_slot'=>'nullable|integer|between:1,3','share_units'=>'integer|between:0,5','exclusive_sponsorship'=>'boolean','reference_total_cents'=>'nullable|integer|between:5,1000000000','status'=>'in:prospect,agreed,active,cancelled'])->validate();
  if(($p->latitude!==null||$p->longitude!==null)&&($p->latitude===null||$p->longitude===null||blank($p->location_evidence)))throw ValidationException::withMessages(['location_evidence'=>'Coordonnées complètes et emplacement vérifié requis avant affichage sur la carte.']);
  if($p->exists&&$p->isDirty('stand_id'))throw ValidationException::withMessages(['stand_id'=>'Conserver le stand du dossier.']);
  if(!$p->main_slot&&!$p->relay_slot&&!$p->share_units)throw ValidationException::withMessages(['main_slot'=>'Choisir des parts de parrainage et/ou une place de relais.']);
  if($p->stand?->kind!=='village')throw ValidationException::withMessages(['stand_id'=>'Le parrainage local concerne un stand de freguesia.']);
  app(\App\Domain\Promotion\StandSponsoring::class)->validatePartner($p);
  foreach(['main_slot','relay_slot'] as $slot)if($p->$slot&&self::where('stand_id',$p->stand_id)->where($slot,$p->$slot)->when($p->exists,fn($q)=>$q->where('id','!=',$p->id))->exists())throw ValidationException::withMessages([$slot=>'Cette place est déjà attribuée.']);
  if($p->hosts_information_meeting&&$p->stand->eventProject->community_version){if(!$p->meeting_capacity||blank($p->meeting_capacity_evidence))throw ValidationException::withMessages(['meeting_capacity'=>'Capacité réellement disponible à vérifier.']);$larger=self::where('stand_id',$p->stand_id)->where('meeting_candidate',true)->whereNotNull('meeting_capacity_evidence')->where('meeting_capacity','>',$p->meeting_capacity)->exists();if($larger&&blank($p->meeting_selection_reason))throw ValidationException::withMessages(['meeting_selection_reason'=>'Un café offre davantage de place : documenter pourquoi retenir celui-ci (disponibilité, accès, date).']);}
  if($p->hosts_information_meeting&&(!$p->relay_slot||$p->status!=='active'||!$p->meeting_at||blank($p->meeting_place)||blank($p->meeting_agreement)))throw ValidationException::withMessages(['meeting_agreement'=>'Réunion : relais actif, lieu, date et accord requis.']);
  if($p->status==='active'&&(blank($p->mission)||blank($p->evidence)))throw ValidationException::withMessages(['evidence'=>'Documenter la mission et son activation.']);
 });}
}
