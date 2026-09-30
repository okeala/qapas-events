<?php
namespace App\Models;
class Scenario extends Record {
 protected $attributes=['months'=>1,'target_surplus_cents'=>400000,'organizer_net_monthly_cents'=>100000,'costs_complete'=>false,'team_target'=>0,'stand_target'=>0,'shelter_model'=>'common','launch_model'=>false,'minimum_daily_activities'=>3,'independent_target'=>0];
protected function casts(): array {return ['costs_complete'=>'boolean','launch_model'=>'boolean'];}
 public function eventProject() {return $this->belongsTo(EventProject::class);}
 public function includedActivities(){return $this->belongsToMany(Activity::class);}
 public function budgetLines() {return $this->hasMany(BudgetLine::class);}
 public function includedStands(){return $this->belongsToMany(Stand::class);}
 public function includedFeatures(){return $this->belongsToMany(SiteFeature::class,'scenario_site_feature');}
 public function programSlots(){return $this->hasMany(ProgramSlot::class);}
 public function parent(){return $this->belongsTo(self::class,'parent_id');}
 protected static function booted(): void {parent::booted();static::saving(function(self $s){
  if(!in_array($s->shelter_model,['common','distributed'],true))throw \Illuminate\Validation\ValidationException::withMessages(['shelter_model'=>'Modèle d’abri inconnu.']);
  if($s->parent_id){$seen=[$s->id];$next=$s->parent_id;while($next){if(in_array($next,$seen))throw \Illuminate\Validation\ValidationException::withMessages(['parent_id'=>'Cycle de paliers interdit.']);$seen[]=$next;$parent=self::find($next);if(!$parent||$parent->event_project_id!==$s->event_project_id)throw \Illuminate\Validation\ValidationException::withMessages(['parent_id'=>'Choisir un palier de cette édition.']);$next=$parent->parent_id;}}
 });}
 public function report(): array {return app(\App\Domain\Finance\ScenarioCalculator::class)->calculate($this);}
}
