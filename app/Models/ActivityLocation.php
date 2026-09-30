<?php
namespace App\Models;
use App\Domain\Planning\{GeoGeometry,GeoArea};
use Illuminate\Validation\ValidationException;
class ActivityLocation extends Record {
 protected $attributes=['role'=>'performance','access'=>'restricted','is_public'=>false];
 protected function casts(): array {return ['geometry'=>'array','additional_quartel_ids'=>'array','is_public'=>'boolean'];}
 public function activity(){return $this->belongsTo(Activity::class);}
 public function siteFeature(){return $this->belongsTo(SiteFeature::class);}
 public function materials(){return $this->hasMany(ActivityMaterial::class);}
 public function validateFootprint(?SiteFeature $replacement=null): void {
  $a=Activity::find($this->activity_id);$ids=array_values(array_unique(array_merge([(int)$this->site_feature_id],array_map('intval',$this->additional_quartel_ids??[]))));
  if(count($ids)>20)throw ValidationException::withMessages(['geometry'=>'Au maximum vingt quartéis par implantation.']);
  $features=SiteFeature::whereIn('id',$ids)->get()->keyBy('id');if($replacement&&in_array($replacement->id,$ids,true))$features[$replacement->id]=$replacement;
  if(!$a||$features->count()!==count($ids)||$features->contains(fn($f)=>$f->event_project_id!==$a->event_project_id))throw ValidationException::withMessages(['site_feature_id'=>'Choisir des éléments du site de cette édition.']);
  if($this->geometry!==null){GeoGeometry::validate($this->geometry);if(!in_array($this->geometry['type'],['Polygon','MultiPolygon'],true)||$features->contains(fn($f)=>$f->category!=='quartel')||!GeoArea::coveredBy($this->geometry,$features->pluck('geometry')->all()))throw ValidationException::withMessages(['geometry'=>'L’emprise doit être un polygone entièrement contenu dans les quartéis sélectionnés.']);}
 }
 public function overlaps(): array {if(!$this->geometry)return [];return self::whereHas('activity',fn($q)=>$q->where('event_project_id',$this->activity->event_project_id))->whereNotNull('geometry')->where('id','!=',$this->id)->get()->filter(fn($l)=>GeoArea::overlaps($this->geometry,$l->geometry))->map(fn($l)=>($l->name?:$l->activity->name).' · '.$l->role)->all();}
 protected static function booted(): void {parent::booted();static::saving(function(self $l){
  if($l->exists&&$l->isDirty('activity_id'))throw ValidationException::withMessages(['activity_id'=>'Une implantation reste liée à son épreuve.']);
  if(!in_array($l->role,['performance','spectator','queue','technical'],true)||!in_array($l->access,['public','restricted','staff'],true))throw ValidationException::withMessages(['role'=>'Rôle ou accès inconnu.']);
  $l->validateFootprint();
 });static::saved(function(self $l){$a=$l->activity;$a->risk_reviewed=false;if($a->status==='approved')$a->status='testing';$a->save();});}
}
