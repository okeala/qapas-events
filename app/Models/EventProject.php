<?php
namespace App\Models;
class EventProject extends Record {
 protected $attributes=['phase'=>'concept','is_public'=>false,'capacity'=>0];
protected function casts(): array {return ['proposed_start'=>'date','proposed_end'=>'date','require_activity_trials'=>'boolean','rehearsal_weekend_start'=>'date','rehearsal_weekend_end'=>'date','is_public'=>'boolean','plan_is_public'=>'boolean','geo_is_public'=>'boolean','starts_at'=>'datetime','ends_at'=>'datetime'];}
 public function launchScenario(): ?Scenario {return $this->scenarios()->where('is_archived',false)->whereIn('template_key',['costing-rental-experts-v1','launch-hospitality-v1','launch-six-six'])->orderByRaw("CASE template_key WHEN 'costing-rental-experts-v1' THEN 0 WHEN 'launch-hospitality-v1' THEN 1 ELSE 2 END")->first();}
 public function registrationCampaign(){return $this->hasOne(RegistrationCampaign::class);}
 public function pressReleases(){return $this->hasMany(PressRelease::class);}
 public function stands(){return $this->hasMany(Stand::class);}
 public function siteFeatures(){return $this->hasMany(SiteFeature::class);}
 public function terraces(){return $this->hasMany(Terrace::class);}
 public function scenarios() {return $this->hasMany(Scenario::class);}
 public function offers() {return $this->hasMany(Offer::class);}
 public function requirements() {return $this->hasMany(LegalRequirement::class);}
 public function teams() {return $this->hasMany(Team::class);}
 public function activities() {return $this->hasMany(Activity::class);}
 public function runItems() {return $this->hasMany(RunItem::class);}
 public function interests() {return $this->hasMany(Interest::class);}
 public function ideas() {return $this->hasMany(Idea::class);}
 public function incidents() {return $this->hasMany(Incident::class);}
 protected static function booted(): void {parent::booted();static::saving(function(self $p){
  if($p->rehearsal_weekend_start||$p->rehearsal_weekend_end){if(!$p->rehearsal_weekend_start||!$p->rehearsal_weekend_end||$p->rehearsal_weekend_end->lt($p->rehearsal_weekend_start)||($p->starts_at&&$p->rehearsal_weekend_end->gte($p->starts_at->copy()->startOfDay())))throw \Illuminate\Validation\ValidationException::withMessages(['rehearsal_weekend_end'=>'Préciser la période complète des répétitions, avant le jour d’ouverture.']);}
 });}
}
