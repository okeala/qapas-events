<?php
namespace App\Models;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class DrawingEntry extends Record {
 protected $attributes=['status'=>'received'];
 protected $hidden=['guardian_contact','guardian_evidence'];
 protected function casts(): array {return ['guardian_contact'=>'encrypted','awarded_at'=>'datetime'];}
 public function contest(){return $this->belongsTo(DrawingContest::class,'drawing_contest_id');}
 public function stand(){return $this->belongsTo(Stand::class);}
 public function save(array $options=[]){return DB::transaction(function()use($options){DrawingContest::whereKey($this->drawing_contest_id)->lockForUpdate()->firstOrFail();return parent::save($options);},3);}
 protected static function booted(): void {parent::booted();static::saving(function(self $e){
  if($e->stand?->event_project_id!==$e->contest?->event_project_id||$e->stand->kind!=='village')throw ValidationException::withMessages(['stand_id'=>'Freguesia participante de cette édition requise.']);
  if($e->exists&&$e->isDirty(['drawing_contest_id','stand_id']))throw ValidationException::withMessages(['stand_id'=>'Conserver le concours et la catégorie.']);
  if(!$e->exists&&(!$e->contest->is_open||!$e->contest->ready()||now()->lt($e->contest->opens_at)||now()->gt($e->contest->closes_at)))throw ValidationException::withMessages(['drawing_contest_id'=>'Dépôt hors période du concours ouvert.']);
  if(!in_array($e->status,['received','eligible','winner','withdrawn'])||blank($e->guardian_contact)||blank($e->guardian_evidence)||blank($e->artwork_reference))throw ValidationException::withMessages(['guardian_evidence'=>'Contact du responsable, accord de participation et référence du dessin papier requis.']);
  if(self::where('drawing_contest_id',$e->drawing_contest_id)->where('artwork_reference',$e->artwork_reference)->when($e->exists,fn($q)=>$q->whereKeyNot($e->id))->exists())throw ValidationException::withMessages(['artwork_reference'=>'Ce dessin est déjà enregistré dans le concours.']);
  $e->winner_key=null;if($e->status==='winner'){
   if(!$e->contest->closes_at||$e->contest->closes_at->isFuture()||blank($e->jury_decision))throw ValidationException::withMessages(['status'=>'Clore les dépôts avant délibération motivée du jury.']);
   $e->winner_key=$e->drawing_contest_id.'-'.$e->stand_id;
   if(self::where('winner_key',$e->winner_key)->when($e->exists,fn($q)=>$q->whereKeyNot($e->id))->exists())throw ValidationException::withMessages(['status'=>'Une seule récompense par freguesia.']);
  }
  if($e->awarded_at&&($e->status!=='winner'||$e->awarded_at->isFuture()||blank($e->award_evidence)))throw ValidationException::withMessages(['awarded_at'=>'Consigner la remise réelle du prix à la junta, président ou représentant et responsable de l’enfant présents.']);
 });}
}
