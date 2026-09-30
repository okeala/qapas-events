<?php
namespace App\Models;
use Illuminate\Validation\ValidationException;
class ReportPoint extends Record {
 public const KEY_CATEGORIES=['toilet','parking','entrance','exit','bar','soup','water','power','waste','screen','emergency'];
 protected $attributes=['is_active'=>false];
 protected function casts(): array {return ['is_active'=>'boolean'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function stand(){return $this->belongsTo(Stand::class);}
 public function feature(){return $this->belongsTo(SiteFeature::class,'site_feature_id');}
 public function url(): string {return route('incident.report',['point'=>$this->public_id]);}
 public function qrSvg(): string {return (new \chillerlan\QRCode\QRCode(new \chillerlan\QRCode\QROptions(['outputType'=>\chillerlan\QRCode\Output\QROutputInterface::MARKUP_SVG,'outputBase64'=>false])))->render($this->url());}
 public static function prepare(EventProject $project): void {
  foreach($project->stands as $s)self::firstOrCreate(['stand_id'=>$s->id],['event_project_id'=>$project->id,'name'=>'Stand · '.($s->pitch_number?:$s->id)]);
  foreach($project->siteFeatures()->whereIn('category',self::KEY_CATEGORIES)->get() as $f)self::firstOrCreate(['site_feature_id'=>$f->id],['event_project_id'=>$project->id,'name'=>'Point · '.$f->id]);
 }
 protected static function booted(): void {parent::booted();static::saving(function(self $p){
  if((bool)$p->stand_id===(bool)$p->site_feature_id)throw ValidationException::withMessages(['stand_id'=>'Choisir un stand OU un lieu-clé.']);
  if(($p->stand_id&&$p->stand?->event_project_id!==$p->event_project_id)||($p->site_feature_id&&$p->feature?->event_project_id!==$p->event_project_id))throw ValidationException::withMessages(['stand_id'=>'Lieu hors édition.']);
  if($p->exists&&$p->isDirty(['stand_id','site_feature_id','event_project_id']))throw ValidationException::withMessages(['stand_id'=>'Ce QR reste rattaché à son lieu.']);
  if($p->is_active&&blank($p->placement_evidence))throw ValidationException::withMessages(['placement_evidence'=>'Vérifier le libellé public, le QR posé sur place et la personne de permanence.']);
 });}
}
