<?php
namespace App\Models;
class Team extends Record {
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function roleAssignments(){return $this->hasMany(TeamRoleAssignment::class);}
 public function composition(): array {return app(\App\Domain\Teams\TeamComposition::class)->report($this);}
 protected static function booted(): void {parent::booted();static::created(fn(self $team)=>\App\Domain\Teams\ExpertRoles::seedSlots($team));static::saving(function(self $team){
  if($team->exists&&$team->isDirty('event_project_id'))throw \Illuminate\Validation\ValidationException::withMessages(['event_project_id'=>'Une équipe reste dans son édition.']);
  if($team->status==='elected'&&$team->isDirty('status')&&(!$team->exists||!$team->composition()['complete']||blank($team->election_minutes)))throw \Illuminate\Validation\ValidationException::withMessages(['status'=>'Pour consigner le résultat : rôles indispensables confirmés, cumuls examinés et procès-verbal du vote local.']);
 });}
}
