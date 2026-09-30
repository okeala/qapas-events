<?php
namespace App\Models;
class Incident extends Record {
protected function casts(): array {return ['acknowledged_at'=>'datetime','closed_at'=>'datetime'];}
 public function point(){return $this->belongsTo(ReportPoint::class,'report_point_id');}
 protected static function booted(): void {parent::booted();static::saving(function(self $i){if($i->report_point_id&&$i->point?->event_project_id!==$i->event_project_id)throw \Illuminate\Validation\ValidationException::withMessages(['report_point_id'=>'Lieu hors édition.']);if($i->exists&&$i->isDirty(['event_project_id','report_point_id','report','source','submission_id']))throw \Illuminate\Validation\ValidationException::withMessages(['report'=>'Conserver le signalement original ; consigner les précisions dans les actions.']);if($i->status==='handled'&&!$i->acknowledged_at)$i->acknowledged_at=now();if($i->status==='closed'){if(blank($i->action))throw \Illuminate\Validation\ValidationException::withMessages(['action'=>'Documenter la résolution avant clôture.']);$i->closed_at=$i->closed_at?:now();}});}
 public function eventProject() {return $this->belongsTo(EventProject::class);}
}
