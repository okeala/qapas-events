<?php
namespace App\Models;
use App\Domain\Teams\ExpertRoles;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\{Rule,ValidationException};
class TeamRoleAssignment extends Record {
 protected $attributes=['status'=>'vacant','consent_confirmed'=>false];
 protected function casts(): array {return ['consent_confirmed'=>'boolean','reviewed_at'=>'datetime'];}
 public function team(){return $this->belongsTo(Team::class);}
 public function interest(){return $this->belongsTo(Interest::class);}
 public function isCovered(): bool {return (!$this->team->eventProject->registrationCampaign||($this->interest?->registration?->isPaid()&&$this->interest?->registration?->decision!=='not_selected'))&&$this->status==='confirmed'&&$this->consent_confirmed&&filled($this->candidate_name)&&filled($this->candidate_reference)&&filled($this->competence_evidence)&&$this->reviewed_at&&!$this->reviewed_at->isFuture()&&$this->reviewed_by!==null;}
 protected static function booted(): void {parent::booted();static::saving(function(self $a){
  Validator::make($a->getAttributes(),['role_code'=>['required',Rule::in(ExpertRoles::CODES)],'candidate_name'=>'nullable|string|max:120','candidate_reference'=>'nullable|string|max:120','status'=>'in:vacant,proposed,confirmed','competence_evidence'=>'nullable|string|max:5000'])->validate();
  if($a->exists&&$a->isDirty(['team_id','role_code']))throw ValidationException::withMessages(['role_code'=>'Le poste reste rattaché à son équipe et à son rôle.']);
  if($a->candidate_reference)$a->candidate_reference=mb_strtolower(trim($a->candidate_reference));
  if($a->interest_id){$interest=Interest::whereKey($a->interest_id)->where('event_project_id',$a->team?->event_project_id)->first();if(!$interest||!in_array($a->role_code,$interest->expert_roles??[],true))throw ValidationException::withMessages(['interest_id'=>'Choisir une candidature de cette édition proposant ce rôle.']);}
  if($a->status!=='vacant'&&blank($a->candidate_name))throw ValidationException::withMessages(['candidate_name'=>'Indiquer la personne proposée pour ce rôle.']);
  if($a->exists&&$a->getOriginal('status')==='confirmed'&&$a->isDirty(['candidate_name','candidate_reference','interest_id','consent_confirmed','competence_evidence']))$a->status='proposed';
  if($a->status!=='confirmed'){$a->reviewed_at=null;$a->reviewed_by=null;}
  elseif(!$a->exists||$a->getOriginal('status')!=='confirmed'){
   if(!auth('admin')->user()?->is_active||!$a->consent_confirmed||blank($a->candidate_name)||blank($a->candidate_reference)||blank($a->competence_evidence))throw ValidationException::withMessages(['status'=>'Vérifier consentement, personne, référence au registre local et compétence avant confirmation.']);
   if($a->team->eventProject->registrationCampaign&&(!$a->interest?->registration?->isPaid()||$a->interest?->registration?->decision==='not_selected'))throw ValidationException::withMessages(['status'=>'Candidature liée et paiement de 10 € confirmé requis pour cette édition.']);
   $a->reviewed_at=now();$a->reviewed_by=auth('admin')->id();
  }
  // One locally identified person belongs to one team per edition. Several roles in that same team require a separate cumulation review.
  if($a->status==='confirmed'&&self::whereHas('team',fn($q)=>$q->where('event_project_id',$a->team->event_project_id))->where('team_id','!=',$a->team_id)->where('status','confirmed')->where(function($q)use($a){$q->where('candidate_reference',$a->candidate_reference);if($a->interest_id)$q->orWhere('interest_id',$a->interest_id);})->exists())throw ValidationException::withMessages(['candidate_reference'=>'Cette personne est déjà confirmée dans une autre équipe de cette édition.']);
 });static::saved(function(self $a){if($a->wasChanged(['candidate_reference','candidate_name','interest_id']))$a->team()->update(['role_cumulation_evidence'=>null]);});}
}
