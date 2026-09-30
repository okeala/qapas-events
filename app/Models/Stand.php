<?php
namespace App\Models;
class Stand extends Record {
 protected function casts(): array {return ['direct_costs_complete'=>'boolean','hospitality_validated'=>'boolean','larger_tent_requested'=>'boolean'];}
 public function siteFeature(){return $this->belongsTo(SiteFeature::class);}
 public function partners(){return $this->hasMany(StandPartner::class);}
 protected static function booted(): void {parent::booted();static::saving(function(self $s){\Illuminate\Support\Facades\Validator::make($s->getAttributes(),['shelter_target'=>'sometimes|integer|between:1,10000','sheltered_capacity'=>'nullable|integer|between:0,10000','seated_capacity'=>'nullable|integer|between:0,10000','included_furniture_sets'=>'sometimes|integer|between:0,1000','extra_furniture_sets'=>'sometimes|integer|between:0,1000','shelter_source'=>'sometimes|in:undecided,own,loan,qapas'])->validate();if($s->exists&&$s->isDirty(['shelter_target','sheltered_capacity','seated_capacity','included_furniture_sets','extra_furniture_sets','shelter_source','larger_tent_requested']))$s->hospitality_validated=false;if($s->hospitality_validated&&blank($s->hospitality_evidence))throw \Illuminate\Validation\ValidationException::withMessages(['hospitality_evidence'=>'Documenter l’abri, les assises, les espaces debout et les circulations.']);if($s->site_feature_id&&!SiteFeature::whereKey($s->site_feature_id)->where('event_project_id',$s->event_project_id)->exists())throw \Illuminate\Validation\ValidationException::withMessages(['site_feature_id'=>'Implantation hors édition.']);});}
 public function eventProject() {return $this->belongsTo(EventProject::class);}
}
