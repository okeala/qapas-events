<?php
namespace App\Models;
use App\Domain\Planning\ActivityPreparation;
use Illuminate\Validation\ValidationException;
class ActivityTrial extends Record {
 protected $attributes=['result'=>'planned'];
 protected function casts(): array {return ['planned_at'=>'datetime','performed_at'=>'datetime','reviewed_at'=>'datetime'];}
 public function activity(){return $this->belongsTo(Activity::class);}
 protected static function booted(): void {parent::booted();static::saving(function(self $t){
  \Illuminate\Support\Facades\Validator::make($t->getAttributes(),['stage'=>'required|in:official_rehearsal,local_test,site_installation','result'=>'required|in:planned,passed,failed','volunteers'=>'nullable|integer|between:1,1000','duration_seconds'=>'nullable|integer|between:1,86400'])->validate();
  if($t->exists&&$t->isDirty(['activity_id','stage']))throw ValidationException::withMessages(['stage'=>'Conserver l’épreuve et la phase de cet essai ; créer un nouvel essai pour une autre phase.']);
  $a=$t->activity;if(!$a||($a->proposer_type==='organization')!==($t->stage==='official_rehearsal'))throw ValidationException::withMessages(['stage'=>'Répétition pour QAPAS ; essai local puis installation pour les stands.']);
  if($t->exists&&$t->getOriginal('result')==='passed'&&$t->isDirty(['planned_at','performed_at','location','responsible','volunteers','duration_seconds','evidence','adjustments'])){$t->result='planned';$t->reviewed_at=null;$t->reviewed_by=null;$t->protocol_hash=null;}
  if($t->result==='passed'){
   if(!auth('admin')->user()?->is_active||!$t->performed_at||$t->performed_at->isFuture()||blank($t->evidence)||blank($t->responsible)||blank($t->location)||!$t->volunteers)throw ValidationException::withMessages(['result'=>'Essai effectué, lieu, responsable, volontaires et compte rendu requis pour valider.']);
   $service=app(ActivityPreparation::class);if($t->stage==='site_installation'){$start=$a->eventProject->starts_at;$local=$service->validTrial($a,'local_test');if(!$start||$t->performed_at->lt($start->copy()->subDays(7))||$t->performed_at->gte($start)||!$local||$local->performed_at->gt($t->performed_at))throw ValidationException::withMessages(['result'=>'Installation durant la semaine avant ouverture, après un essai local validé.']);}
   if($a->eventProject->starts_at&&$t->performed_at->gte($a->eventProject->starts_at))throw ValidationException::withMessages(['performed_at'=>'Effectuer les essais avant le coup d’envoi.']);
   $t->protocol_hash=$service->fingerprint($a,$t->stage);$t->reviewed_at=now();$t->reviewed_by=auth('admin')->id();
  }
 });}
}
