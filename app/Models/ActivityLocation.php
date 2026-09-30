<?php
namespace App\Models;
use Illuminate\Validation\ValidationException;
class ActivityLocation extends Record {
 protected $attributes=['role'=>'performance'];
 public function activity(){return $this->belongsTo(Activity::class);}
 public function siteFeature(){return $this->belongsTo(SiteFeature::class);}
 protected static function booted(): void {parent::booted();static::saving(function(self $l){
  $a=Activity::find($l->activity_id);$f=SiteFeature::find($l->site_feature_id);
  if(!$a||!$f||$a->event_project_id!==$f->event_project_id)throw ValidationException::withMessages(['site_feature_id'=>'Choisir un élément du site de cette édition.']);
  if(!in_array($l->role,['performance','spectator','queue','technical'],true))throw ValidationException::withMessages(['role'=>'Rôle inconnu.']);
 });static::saved(function(self $l){$a=$l->activity;$a->risk_reviewed=false;if($a->status==='approved')$a->status='testing';$a->save();});}
}
