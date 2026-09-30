<?php
namespace App\Models;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
class FreguesiaInvitation extends Record {
 protected $attributes=['status'=>'planned','is_public'=>false];
 protected function casts(): array {return ['is_public'=>'boolean','invited_at'=>'datetime','accepted_at'=>'datetime'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function prospect(){return $this->belongsTo(Prospect::class);}
 public function teams(){return $this->hasMany(Team::class);}
 public function publicStatus(): string {if($this->status==='declined')return 'declined';if($this->status==='accepted'&&$this->accepted_at?->lte(now())&&filled($this->acceptance_evidence))return 'accepted';return in_array($this->status,['invited','accepted'])&&$this->invited_at?->lte(now())&&filled($this->invitation_evidence)?'invited':'planned';}
 protected static function booted(): void {parent::booted();static::saving(function(self $i){
  Validator::make($i->getAttributes(),['name'=>'required|string|max:255','municipality'=>'required|string|max:255','source_key'=>'required|string|max:255','status'=>'required|in:planned,invited,accepted,declined','source_url'=>'nullable|url:http,https|max:1000'])->validate();
  if($i->exists&&$i->isDirty('event_project_id'))throw ValidationException::withMessages(['event_project_id'=>'Conserver l’édition.']);
  if($i->prospect_id&&!Prospect::whereKey($i->prospect_id)->where('event_project_id',$i->event_project_id)->exists())throw ValidationException::withMessages(['prospect_id'=>'Prospect d’une autre édition.']);
  if(in_array($i->status,['invited','accepted'])&&(!$i->invited_at||$i->invited_at->isFuture()||blank($i->invitation_evidence)))throw ValidationException::withMessages(['invited_at'=>'Date passée et preuve de remise ou d’envoi requises.']);
  if($i->status==='accepted'&&(!$i->accepted_at||$i->accepted_at->isFuture()||$i->accepted_at->lt($i->invited_at)||blank($i->acceptance_evidence)))throw ValidationException::withMessages(['accepted_at'=>'Réponse effective datée et documentée requise ; la convention reste distincte.']);
 });}
}
