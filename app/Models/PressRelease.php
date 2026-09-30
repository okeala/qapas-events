<?php
namespace App\Models;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
class PressRelease extends Record {
 protected $attributes=['phase'=>'before','kind'=>'mobilization','status'=>'draft','is_public'=>false,'date_anchor'=>'start'];
 protected function casts(): array {return ['depends_on'=>'array','is_public'=>'boolean','target_at'=>'datetime','published_at'=>'datetime'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function scenario(){return $this->belongsTo(Scenario::class);}
 public function plannedDate(){if($this->target_at)return $this->target_at;$date=$this->date_anchor==='end'?$this->eventProject?->ends_at:$this->eventProject?->starts_at;return $date&&$this->day_offset!==null?$date->copy()->addDays($this->day_offset):null;}
 public function publicationProblems(): array {
  $errors=[];
  if(blank($this->owner)||blank($this->body)||blank($this->evidence))$errors[]='Responsable, texte et validation éditoriale documentée requis.';
  foreach($this->depends_on??[] as $id){$step=Idea::find($id);if(!$step||$step->event_project_id!==$this->event_project_id||!$step->isValidated())$errors[]='Jalon préalable non validé.';}
  if($this->kind==='revelation'&&!app(\App\Domain\Planning\RelayMobilization::class)->report($this->eventProject)['ready'])$errors[]='Un relais actif documenté dans chacune des six freguesias est requis.';
  if(!in_array($this->kind,['mobilization','revelation'],true)){
   $scenario=Scenario::find($this->scenario_id);
   if(!$scenario||$scenario->event_project_id!==$this->event_project_id||!$scenario->report()['expansion_ready'])$errors[]='Scénario financé préservant l’objectif QAPAS requis pour une annonce confirmée.';
   if(!$this->eventProject?->starts_at||!$this->eventProject?->ends_at||blank($this->eventProject?->venue))$errors[]='Dates et lieu à confirmer.';
  }
  return array_unique($errors);
 }
 public function publiclyAvailable(): bool {return $this->is_public&&$this->eventProject?->is_public&&$this->status==='published'&&$this->published_at&&$this->published_at->lte(now())&&$this->publicationProblems()===[];}
 protected static function booted(): void {parent::booted();static::saving(function(self $p){
  Validator::make($p->getAttributes(),['phase'=>'in:before,during,after','kind'=>'in:mobilization,revelation,official,reminder,update,results,review','status'=>'in:draft,review,approved,published,archived','date_anchor'=>'in:start,end','day_offset'=>'nullable|integer|between:-365,365'])->validate();
  if($p->exists&&$p->isDirty('event_project_id'))throw ValidationException::withMessages(['event_project_id'=>'Un communiqué reste dans son édition.']);
  if(!is_array($p->depends_on??[])||count($p->depends_on??[])>20)throw ValidationException::withMessages(['depends_on'=>'Vingt jalons maximum.']);
  foreach($p->depends_on??[] as $id)if(!Idea::whereKey($id)->where('event_project_id',$p->event_project_id)->exists())throw ValidationException::withMessages(['depends_on'=>'Jalon hors édition.']);
  if($p->scenario_id&&!Scenario::whereKey($p->scenario_id)->where('event_project_id',$p->event_project_id)->exists())throw ValidationException::withMessages(['scenario_id'=>'Scénario hors édition.']);
  if($p->exists&&$p->isDirty(['name','title_pt','body','body_pt','depends_on','scenario_id','assets_and_rights','kind'])&&in_array($p->getOriginal('status'),['approved','published'],true)){$p->status='review';$p->published_at=null;}
  if(in_array($p->status,['approved','published'],true)&&$p->publicationProblems())throw ValidationException::withMessages(['status'=>implode(' ',$p->publicationProblems())]);
  if($p->status==='published'&&(!$p->published_at||$p->published_at->isFuture()))throw ValidationException::withMessages(['published_at'=>'Indiquer la date réelle, passée ou présente, de publication.']);
 });}
}
