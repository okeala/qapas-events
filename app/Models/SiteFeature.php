<?php
namespace App\Models;
use App\Domain\Planning\GeoGeometry;
use Illuminate\Validation\ValidationException;
class SiteFeature extends Record {
 public const CATEGORIES=['quartel'=>'Quartel / terrasse','stand'=>'Stand','tent'=>'Abri / chapiteau','toilet'=>'Toilettes','parking'=>'Parking','path'=>'Cheminement','entrance'=>'Entrée','exit'=>'Sortie','emergency'=>'Accès secours','water'=>'Eau','power'=>'Électricité','waste'=>'Déchets','screen'=>'Écran / régie','bar'=>'Bar QAPAS','soup'=>'Soupe QAPAS','fries'=>'Ancienne friterie (historique)','spectators'=>'Zone spectateurs'];
 protected $attributes=['access'=>'restricted','is_public'=>false,'needs_complete'=>false];
 protected function casts(): array {return ['geometry'=>'array','is_public'=>'boolean','needs_complete'=>'boolean'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function needs(){return $this->hasMany(SiteNeed::class);}
 public function locations(){return $this->hasMany(ActivityLocation::class);}
 protected static function booted(): void {parent::booted();static::saving(function(self $f){
  GeoGeometry::validate($f->geometry);$f->geometry=['type'=>$f->geometry['type'],'coordinates'=>$f->geometry['coordinates']];
  if(!array_key_exists($f->category,self::CATEGORIES))throw ValidationException::withMessages(['category'=>'Catégorie inconnue.']);
  if($f->category==='quartel'&&!in_array($f->geometry['type'],['Polygon','MultiPolygon'],true))throw ValidationException::withMessages(['geometry'=>'Un quartel doit être un polygone.']);
  if($f->exists&&$f->isDirty('event_project_id'))throw ValidationException::withMessages(['event_project_id'=>'Un objet reste lié à son édition.']);
  if($f->exists&&$f->isDirty(['geometry','category']))foreach(ActivityLocation::whereNotNull('geometry')->get() as $l)if($l->site_feature_id===$f->id||in_array($f->id,array_map('intval',$l->additional_quartel_ids??[]),true))$l->validateFootprint($f);
 });static::saved(function(self $f){if($f->wasChanged(['geometry','access']))foreach(ActivityLocation::whereHas('activity',fn($q)=>$q->where('event_project_id',$f->event_project_id))->get()->filter(fn($l)=>$l->site_feature_id===$f->id||in_array($f->id,array_map('intval',$l->additional_quartel_ids??[]),true)) as $l){$a=$l->activity;$a->risk_reviewed=false;if($a->status==='approved')$a->status='testing';$a->save();}});}
}
