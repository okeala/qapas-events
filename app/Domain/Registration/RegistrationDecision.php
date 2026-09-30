<?php
namespace App\Domain\Registration;
use App\Models\{CandidateRegistration,DrinkCredit,TeamRoleAssignment};
use Illuminate\Support\Facades\{DB,Validator};
use Illuminate\Validation\ValidationException;
final class RegistrationDecision {
 public function record(CandidateRegistration $registration,string $decision,string $evidence): void {
  abort_unless(auth('admin')->user()?->is_active,403);Validator::make(compact('decision','evidence'),['decision'=>'required|in:selected,not_selected','evidence'=>'required|string|min:10|max:5000'])->validate();
  DB::transaction(function()use($registration,$decision,$evidence){$r=CandidateRegistration::lockForUpdate()->findOrFail($registration->id);if(!$r->isPaid())throw ValidationException::withMessages(['decision'=>'Une inscription payée est requise.']);
   if(!$r->campaign->vote_closed_at||$r->campaign->vote_closed_at->isFuture()||blank($r->campaign->vote_minutes_reference))throw ValidationException::withMessages(['decision'=>'Consigner la clôture du scrutin et son procès-verbal dans la campagne.']);
   if($r->decision!=='pending'){if($r->decision===$decision)return;throw ValidationException::withMessages(['decision'=>'Résultat déjà consigné : traiter une contestation avec son historique avant correction.']);}
   $selected=TeamRoleAssignment::where('interest_id',$r->interest_id)->where('status','confirmed')->whereHas('team',fn($q)=>$q->where('status','elected')->whereNotNull('election_minutes'))->exists();
   if(($decision==='selected')!==$selected)throw ValidationException::withMessages(['decision'=>'Le résultat doit correspondre aux rôles confirmés dans une équipe dont le vote est consigné.']);
   $r->update(['decision'=>$decision,'vote_evidence'=>$evidence,'decision_at'=>now(),'decision_by'=>auth('admin')->id()]);
   if($decision==='not_selected')DrinkCredit::firstOrCreate(['candidate_registration_id'=>$r->id],['face_cents'=>$r->credit_cents]);
  },3);
 }
 public function redeem(DrinkCredit $credit,int $cents,string $description,string $operationId): void {
  abort_unless(auth('admin')->user()?->is_active,403);Validator::make(compact('cents','description','operationId'),['cents'=>'integer|between:1,1000','description'=>'required|string|max:255','operationId'=>'required|uuid'])->validate();
  DB::transaction(function()use($credit,$cents,$description,$operationId){
   $r=CandidateRegistration::lockForUpdate()->findOrFail($credit->candidate_registration_id);$c=DrinkCredit::lockForUpdate()->findOrFail($credit->id);
   $prior=DB::table('drink_redemptions')->where('operation_id',$operationId)->first();if($prior){if($prior->drink_credit_id!==$c->id||$prior->amount_cents!==$cents||$prior->description!==$description)throw ValidationException::withMessages(['operationId'=>'Une opération existe déjà avec un autre contenu.']);return;}
   if(!$r->isPaid()||$r->decision!=='not_selected'||$cents>$c->availableCents())throw ValidationException::withMessages(['cents'=>'Crédit indisponible ou solde insuffisant.']);
   $c->increment('redeemed_cents',$cents);DB::table('drink_redemptions')->insert(['operation_id'=>$operationId,'drink_credit_id'=>$c->id,'amount_cents'=>$cents,'description'=>$description,'admin_id'=>auth('admin')->id(),'created_at'=>now()]);
  },3);
 }
}
