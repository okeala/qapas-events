<?php
namespace App\Models;
use Illuminate\Support\Facades\{DB,Validator};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class EventBadge extends Record {
 protected $attributes=['status'=>'draft'];
 protected function casts(): array {return ['valid_from'=>'datetime','valid_until'=>'datetime','issued_at'=>'datetime','revoked_at'=>'datetime'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function welcomePackPlan(){return $this->belongsTo(WelcomePackPlan::class);}
 public function holder(): ?array {return collect($this->welcomePackPlan?->people??[])->firstWhere('person_key',$this->person_key);}
 public function playerEligible(): bool {return $this->role!=='players'||collect($this->welcomePackPlan?->electedPeople()??[])->contains('person_key',$this->person_key);}
 public function valid(): bool {return $this->status==='active'&&$this->playerEligible()&&$this->issued_at&&$this->issued_at->lte(now())&&$this->valid_from?->lte(now())&&$this->valid_until?->gte(now())&&($this->holder()['category']??null)===$this->role;}
 public function url(): string {return route('badge.verify',['badge'=>$this->public_id]);}
 public function qrSvg(): string {return (new \chillerlan\QRCode\QRCode(new \chillerlan\QRCode\QROptions(['outputType'=>\chillerlan\QRCode\Output\QROutputInterface::MARKUP_SVG,'outputBase64'=>false])))->render($this->url());}
 public function save(array $options=[]){return DB::transaction(function()use($options){EventProject::whereKey($this->event_project_id)->lockForUpdate()->firstOrFail();return parent::save($options);},3);}
 protected static function booted(): void {parent::booted();static::saving(function(self $b){
  foreach(['welcome_pack_plan_id'=>'welcomePackPlan','event_project_id'=>'eventProject'] as $key=>$rel)if($b->isDirty($key))$b->unsetRelation($rel);
  Validator::make($b->getAttributes(),['role'=>'required|in:players,qapas,service','status'=>'required|in:draft,active,revoked','person_key'=>'required|string|max:120'])->validate();
  if(!$b->exists)$b->serial='JG-'.strtoupper(Str::random(12));
  if($b->welcomePackPlan?->event_project_id!==$b->event_project_id)throw ValidationException::withMessages(['welcome_pack_plan_id'=>'Lot d’une autre édition.']);
  if($b->exists&&$b->isDirty(['event_project_id','welcome_pack_plan_id','person_key','role','serial']))throw ValidationException::withMessages(['person_key'=>'Un badge ne change ni de titulaire, ni de rôle, ni d’édition : révoquer puis créer un remplacement.']);
  if($b->exists&&$b->getOriginal('status')==='revoked'&&$b->status!=='revoked')throw ValidationException::withMessages(['status'=>'Un badge révoqué ne se réactive pas.']);
  $b->active_key=$b->status==='revoked'?null:mb_strtolower(trim($b->person_key));
  if($b->active_key&&self::where('event_project_id',$b->event_project_id)->where('active_key',$b->active_key)->when($b->exists,fn($q)=>$q->whereKeyNot($b->id))->exists())throw ValidationException::withMessages(['person_key'=>'Une personne a déjà un badge ; révoquer l’ancien avant remplacement.']);
  if($b->status==='active'){
   if(!auth('admin')->user()?->is_active||!$b->playerEligible()||($b->holder()['category']??null)!==$b->role||!$b->valid_from||!$b->valid_until||$b->valid_until->lte($b->valid_from)||blank($b->evidence))throw ValidationException::withMessages(['status'=>'Administrateur actif, personne du relevé, rôle, période complète et preuve de délivrance requis.']);
   if(!$b->exists||$b->getOriginal('status')!=='active')$b->issued_at=now();
  }
  if($b->status==='revoked'){if(!auth('admin')->user()?->is_active||blank($b->revocation_reason))throw ValidationException::withMessages(['revocation_reason'=>'Motif privé de révocation requis.']);if(!$b->revoked_at)$b->revoked_at=now();}
 });}
}
