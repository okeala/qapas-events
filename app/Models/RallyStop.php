<?php
namespace App\Models;
use Illuminate\Validation\ValidationException;
class RallyStop extends Record {
 protected $attributes=['is_public'=>false,'sort_order'=>100];
 protected function casts(): array {return ['is_public'=>'boolean'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function stand(){return $this->belongsTo(Stand::class);}
 public function feature(){return $this->belongsTo(SiteFeature::class,'site_feature_id');}
 public function publishable(): bool {return $this->is_public&&$this->eventProject?->is_public&&$this->stand?->is_public&&filled($this->stand?->freguesia)&&filled($this->story_fr)&&filled($this->story_pt)&&filled($this->nature_fr)&&filled($this->nature_pt)&&filled($this->question_fr)&&filled($this->question_pt)&&filled($this->review_evidence)&&filled($this->safety_evidence);}
 protected static function booted(): void {parent::booted();static::saving(function(self $s){
  if($s->stand?->event_project_id!==$s->event_project_id||$s->stand->kind!=='village'||($s->site_feature_id&&$s->feature?->event_project_id!==$s->event_project_id))throw ValidationException::withMessages(['stand_id'=>'Freguesia et lieu de cette édition requis.']);
  if($s->exists&&$s->isDirty(['event_project_id','stand_id']))throw ValidationException::withMessages(['stand_id'=>'Conserver l’étape et sa freguesia.']);
  if($s->is_public&&!$s->publishable())throw ValidationException::withMessages(['is_public'=>'Stand public nommé, histoire, nature, devinette FR/PT et revues culturelle/sécurité requises.']);
 });}
}
