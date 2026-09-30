<?php
namespace App\Models;
use Illuminate\Validation\ValidationException;
class FeedbackCampaign extends Record {
 protected $attributes=['is_enabled'=>false,'version'=>'1'];
 protected function casts(): array {return ['is_enabled'=>'boolean','send_after'=>'datetime','closes_at'=>'datetime'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function invitations(){return $this->hasMany(FeedbackInvitation::class);}
 public function accepting(): bool {return $this->is_enabled&&$this->eventProject?->is_public&&$this->eventProject->ends_at&&$this->eventProject->ends_at->isPast()&&$this->closes_at&&$this->closes_at->isFuture();}
 protected static function booted(): void {parent::booted();static::saving(function(self $c){
  if($c->exists&&$c->isDirty('event_project_id'))throw ValidationException::withMessages(['event_project_id'=>'Conserver l’édition.']);
  if($c->exists&&$c->isDirty(['body_fr','body_pt','version','send_after','closes_at','authorization']))$c->is_enabled=false;
  if($c->is_enabled&&(!$c->eventProject->ends_at||!$c->send_after||$c->send_after->lte($c->eventProject->ends_at)||!$c->closes_at||$c->closes_at->lte($c->send_after)||blank($c->authorization)||blank($c->body_fr)||blank($c->body_pt)))throw ValidationException::withMessages(['is_enabled'=>'Fin de l’événement, délai après clôture, textes FR/PT, échéance et autorisation d’envoi requis.']);
 });}
}
