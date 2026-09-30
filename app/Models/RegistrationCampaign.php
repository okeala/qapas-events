<?php
namespace App\Models;
use Illuminate\Validation\ValidationException;
class RegistrationCampaign extends Record {
 protected $attributes=['is_open'=>false,'drinks_allocated_cents'=>0];
 protected function casts(): array {return ['is_open'=>'boolean','closes_at'=>'datetime','reviewed_at'=>'datetime','vote_closed_at'=>'datetime'];}
 public function eventProject(){return $this->belongsTo(EventProject::class);}
 public function registrations(){return $this->hasMany(CandidateRegistration::class);}
 public function blockers(): array {return app(\App\Domain\Registration\RegistrationGate::class)->blockers($this);}
 public function funding(): array {return app(\App\Domain\Registration\RegistrationFunding::class)->report($this);}
 protected static function booted(): void {parent::booted();static::saving(function(self $c){
  \Illuminate\Support\Facades\Validator::make($c->getAttributes(),['bank_available_cents'=>'nullable|integer|between:0,100000000','vat_basis_points'=>'nullable|integer|between:0,10000','terms_version'=>'required|string|max:100','drinks_contract_cents'=>'nullable|integer|min:0|max:100000000','drinks_allocated_cents'=>'integer|min:0|max:100000000','refund_reserve_cents'=>'nullable|integer|min:0|max:100000000'])->validate();
  if($c->exists&&$c->isDirty('event_project_id'))throw ValidationException::withMessages(['event_project_id'=>'Campagne liée à son édition.']);
  if($c->exists&&$c->isDirty(['terms_version','terms_fr','terms_pt','refund_policy_fr','refund_policy_pt','vat_basis_points','stripe_tax_rate_id','billing_procedure','closes_at','validation_evidence'])){
   if($c->registrations()->exists()&&!$c->isDirty('terms_version'))throw ValidationException::withMessages(['terms_version'=>'Changer de version lorsque les conditions évoluent ; les inscriptions conservent leur texte accepté.']);
   $c->is_open=false;$c->reviewed_at=null;$c->reviewed_by=null;
  }
  if($c->is_open&&(!$c->exists||$c->isDirty('is_open'))){if(!auth('admin')->user()?->is_active)throw ValidationException::withMessages(['is_open'=>'Administrateur actif requis.']);$blockers=$c->blockers();if($blockers)throw ValidationException::withMessages(['is_open'=>implode(' · ',$blockers)]);$c->reviewed_at=now();$c->reviewed_by=auth('admin')->id();}
  if($c->isDirty('vote_closed_at')&&$c->vote_closed_at){if(!auth('admin')->user()?->is_active||$c->vote_closed_at->isFuture()||!$c->closes_at||$c->closes_at->isFuture()||blank($c->vote_minutes_reference))throw ValidationException::withMessages(['vote_closed_at'=>'Clore les candidatures et documenter les résultats du vote local avant leur application.']);$c->is_open=false;}
  if($c->drinks_allocated_cents>0){$f=$c->funding();if($c->refund_reserve_cents===null||blank($c->funding_evidence)||blank($c->drinks_supplier)||blank($c->drinks_contract_reference)||$c->drinks_contract_cents===null||$c->drinks_allocated_cents>$c->drinks_contract_cents||$c->drinks_allocated_cents>$f['available_cents'])throw ValidationException::withMessages(['drinks_allocated_cents'=>'Documenter fournisseur, contrat, réserve et encaissements nets suffisants.']);}
 });}
}
