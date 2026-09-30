<?php
namespace App\Models;
class Stand extends Record {
 protected function casts(): array {return ['direct_costs_complete'=>'boolean'];}
 public function siteFeature(){return $this->belongsTo(SiteFeature::class);}
 public function partners(){return $this->hasMany(StandPartner::class);}
 protected static function booted(): void {parent::booted();static::saving(function(self $s){if($s->site_feature_id&&!SiteFeature::whereKey($s->site_feature_id)->where('event_project_id',$s->event_project_id)->exists())throw \Illuminate\Validation\ValidationException::withMessages(['site_feature_id'=>'Implantation hors édition.']);});}
 public function eventProject() {return $this->belongsTo(EventProject::class);}
}
