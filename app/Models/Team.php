<?php
namespace App\Models;
class Team extends Record {
 protected function casts(): array {return ['is_public'=>'boolean','is_demo'=>'boolean'];}
 public function freguesiaInvitation(){return $this->belongsTo(FreguesiaInvitation::class);}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function roleAssignments(){return $this->hasMany(TeamRoleAssignment::class);}
 public function composition(): array {return app(\App\Domain\Teams\TeamComposition::class)->report($this);}
 protected static function booted(): void {parent::booted();static::created(fn(self $team)=>\App\Domain\Teams\ExpertRoles::seedSlots($team));static::saving(function(self $team){
  if($team->exists&&$team->isDirty(['is_demo','demo_key']))throw \Illuminate\Validation\ValidationException::withMessages(['is_demo'=>'Une démonstration ne devient pas une vraie équipe.']);
  if($team->is_demo&&$team->status!=='forming')throw \Illuminate\Validation\ValidationException::withMessages(['status'=>'Équipe fictive : aucune candidature ou élection réelle.']);
  if($team->freguesia_invitation_id&&!FreguesiaInvitation::whereKey($team->freguesia_invitation_id)->where('event_project_id',$team->event_project_id)->exists())throw \Illuminate\Validation\ValidationException::withMessages(['freguesia_invitation_id'=>'Commune d’une autre édition.']);
  if($team->exists&&$team->isDirty('event_project_id'))throw \Illuminate\Validation\ValidationException::withMessages(['event_project_id'=>'Une équipe reste dans son édition.']);
  if($team->status==='elected'&&$team->isDirty('status')&&(!$team->exists||!$team->composition()['complete']||blank($team->election_minutes)))throw \Illuminate\Validation\ValidationException::withMessages(['status'=>'Pour consigner le résultat : rôles indispensables confirmés, cumuls examinés et procès-verbal du vote local.']);
 });}
}
