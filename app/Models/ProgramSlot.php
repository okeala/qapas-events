<?php
namespace App\Models;
use Illuminate\Validation\ValidationException;
class ProgramSlot extends Record {
 public function scenario(){return $this->belongsTo(Scenario::class);}
 public function activity(){return $this->belongsTo(Activity::class);}
 protected static function booted(): void {parent::booted();static::saving(function(self $s){
  if(!$s->scenario||!$s->activity||$s->scenario->event_project_id!==$s->activity->event_project_id||$s->activity->track!=='official')throw ValidationException::withMessages(['activity_id'=>'Choisir une épreuve officielle de cette édition.']);
  if($s->day_number<1||$s->day_number>31||($s->scenario->event_days&&$s->day_number>$s->scenario->event_days))throw ValidationException::withMessages(['day_number'=>'Jour hors de la durée du scénario.']);
  if(!$s->scenario->includedActivities()->where('activities.id',$s->activity_id)->exists())throw ValidationException::withMessages(['activity_id'=>'Inclure d’abord le coût de cette épreuve dans le scénario.']);
 });}
}
