<?php
namespace App\Models;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
class PressRelease extends Record {
 protected $attributes=['phase'=>'before','kind'=>'mobilization','status'=>'draft','is_public'=>false,'date_anchor'=>'start'];
 protected function casts(): array {return ['auto_dispatch'=>'boolean','channels'=>'array','press_emails'=>'array','sponsor_ids'=>'array','relay_ids'=>'array','depends_on'=>'array','is_public'=>'boolean','target_at'=>'datetime','published_at'=>'datetime'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function scenario(){return $this->belongsTo(Scenario::class);}
 public function plannedDate(){if($this->target_at)return $this->target_at;$date=$this->date_anchor==='end'?$this->eventProject?->ends_at:$this->eventProject?->starts_at;return $date&&$this->day_offset!==null?$date->copy()->addDays($this->day_offset):null;}
 public function publicationProblems(bool $checkMilestones=true): array {
  $errors=[];
  if(blank($this->owner)||blank($this->body)||blank($this->evidence))$errors[]='Responsable, texte et validation éditoriale documentée requis.';
  foreach($checkMilestones?($this->depends_on??[]):[] as $id){$step=Idea::find($id);if(!$step||$step->event_project_id!==$this->event_project_id||!$step->isValidated())$errors[]='Jalon préalable non validé.';}
  if($checkMilestones&&$this->kind==='revelation'&&!app(\App\Domain\Planning\RelayMobilization::class)->report($this->eventProject)['ready'])$errors[]='Un relais actif documenté dans chacune des six freguesias est requis.';
  if($checkMilestones&&!in_array($this->kind,['mobilization','revelation'],true)){
   $scenario=Scenario::find($this->scenario_id);
   if(!$scenario||$scenario->event_project_id!==$this->event_project_id||!$scenario->report()['expansion_ready'])$errors[]='Scénario financé préservant l’objectif QAPAS requis pour une annonce confirmée.';
   if(!$this->eventProject?->starts_at||!$this->eventProject?->ends_at||blank($this->eventProject?->venue))$errors[]='Dates et lieu à confirmer.';
  }
  foreach($this->sponsor_ids??[] as $id){$s=Sponsorship::find($id);if(!$s||$s->event_project_id!==$this->event_project_id||!$s->visible())$errors[]='Sponsor cité sans accord/droits/visibilité acquis.';}
  foreach($this->relay_ids??[] as $id){$r=StandPartner::find($id);if(!$r||$r->stand?->event_project_id!==$this->event_project_id||!$r->relay_slot||$r->status!=='active'||blank($r->evidence))$errors[]='Relais cité non actif ou hors édition.';}
  return array_unique($errors);
 }
 public function publiclyAvailable(): bool {return $this->is_public&&$this->eventProject?->is_public&&$this->status==='published'&&$this->published_at&&$this->published_at->lte(now())&&$this->publicationProblems()===[];}
 protected static function booted(): void {parent::booted();static::saving(function(self $p){
  \Illuminate\Support\Facades\Validator::make(['channels'=>$p->channels??[],'press_emails'=>$p->press_emails??[]],['channels'=>'array|max:4','channels.*'=>'in:website,email,facebook,x','press_emails'=>'array|max:100','press_emails.*'=>'email|max:254'])->validate();
  if($p->auto_dispatch&&(blank($p->dispatch_authorization)||!count($p->depends_on??[])||!count($p->channels??[])||blank($p->publication_version)))throw ValidationException::withMessages(['auto_dispatch'=>'Version, étapes, canaux et autorisation de diffusion requises.']);
  if($p->auto_dispatch&&in_array('email',$p->channels??[])&&!count($p->press_emails??[]))throw ValidationException::withMessages(['press_emails'=>'Sélectionner les destinataires presse professionnels.']);
  if($p->exists&&$p->isDirty(['name','title_pt','body','body_pt','social_text_pt','sponsor_ids','relay_ids','channels','press_emails','depends_on'])&&PublicationDispatch::where('press_release_id',$p->id)->exists()&&!$p->isDirty('publication_version'))throw ValidationException::withMessages(['publication_version'=>'Un nouveau texte / ciblage nécessite une nouvelle version de diffusion.']);
  Validator::make($p->getAttributes(),['phase'=>'in:before,during,after','kind'=>'in:mobilization,revelation,official,reminder,update,results,review','status'=>'in:draft,review,approved,published,archived','date_anchor'=>'in:start,end','day_offset'=>'nullable|integer|between:-365,365'])->validate();
  if($p->exists&&$p->isDirty('event_project_id'))throw ValidationException::withMessages(['event_project_id'=>'Un communiqué reste dans son édition.']);
  if(!is_array($p->depends_on??[])||count($p->depends_on??[])>20)throw ValidationException::withMessages(['depends_on'=>'Vingt jalons maximum.']);
  foreach($p->depends_on??[] as $id)if(!Idea::whereKey($id)->where('event_project_id',$p->event_project_id)->exists())throw ValidationException::withMessages(['depends_on'=>'Jalon hors édition.']);
  if($p->scenario_id&&!Scenario::whereKey($p->scenario_id)->where('event_project_id',$p->event_project_id)->exists())throw ValidationException::withMessages(['scenario_id'=>'Scénario hors édition.']);
  if($p->exists&&$p->isDirty(['name','title_pt','body','body_pt','depends_on','scenario_id','assets_and_rights','kind','social_text_pt','sponsor_ids','relay_ids','channels','press_emails','dispatch_authorization','auto_dispatch','publication_version'])&&in_array($p->getOriginal('status'),['approved','published'],true)){$p->status='review';$p->published_at=null;}
  if(in_array($p->status,['approved','published'],true)&&$p->publicationProblems(!($p->auto_dispatch&&$p->status==='approved')))throw ValidationException::withMessages(['status'=>implode(' ',$p->publicationProblems())]);
  if($p->status==='published'&&(!$p->published_at||$p->published_at->isFuture()))throw ValidationException::withMessages(['published_at'=>'Indiquer la date réelle, passée ou présente, de publication.']);
 });}
}
