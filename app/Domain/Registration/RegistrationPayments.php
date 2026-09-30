<?php
namespace App\Domain\Registration;
use App\Models\CandidateRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class RegistrationPayments {
 public function checkout(CandidateRegistration $registration): string {
  return DB::transaction(function()use($registration){
   $r=CandidateRegistration::lockForUpdate()->findOrFail($registration->id);$campaign=$r->campaign;if($r->ticket)throw ValidationException::withMessages(['payment'=>'Cette candidature possède déjà un billet de prévente. Utiliser le lien du billet.']);
   if(!app(\App\Domain\Tickets\TicketPayments::class)->configured()||!$campaign->is_open||$campaign->blockers()||$r->terms_version!==$campaign->terms_version)throw ValidationException::withMessages(['payment'=>'Paiement fermé ou conditions modifiées. Contacter l’organisation.']);
   if($r->is_live!==(bool)config('registration.live')||$r->payment_status!=='pending')throw ValidationException::withMessages(['payment'=>'Cette inscription a déjà un paiement ou nécessite une revue.']);
   $gateway=app(StripeGateway::class);
   if($r->stripe_session_id){$session=$gateway->session($r->stripe_session_id);if(($session['status']??null)==='open')return $gateway->checkoutUrl($session);if(($session['status']??null)!=='expired')throw ValidationException::withMessages(['payment'=>'Paiement en cours de confirmation.']);$r->checkout_attempt++;}
   $session=$gateway->create($r);$url=$gateway->checkoutUrl($session);$r->update(['stripe_session_id'=>$session['id'],'checkout_url'=>$url]);return $url;
  },3);
 }
 public function webhook(array $event): void {
  DB::transaction(function()use($event){
   if(DB::table('registration_webhooks')->where('provider_event_id',$event['id'])->exists())return;
   $type=$event['type']??'';$object=$event['data']['object']??[];
   if(in_array($type,['checkout.session.completed','checkout.session.async_payment_succeeded'],true)){
    $r=CandidateRegistration::where('stripe_session_id',$object['id']??'')->lockForUpdate()->first();
    // Return a retryable error for a paid session whose local creation transaction is not committed yet.
    if(!$r&&filled($object['metadata']['registration']??null)){$r=CandidateRegistration::where('public_id',$object['metadata']['registration'])->lockForUpdate()->first();if($r){abort_unless(!$r->stripe_session_id,409);$remote=app(StripeGateway::class)->session($object['id']);abort_unless(($remote['client_reference_id']??null)===$r->public_id&&($remote['metadata']['registration']??null)===$r->public_id&&(string)($remote['metadata']['attempt']??'')===(string)$r->checkout_attempt,422);$r->update(['stripe_session_id'=>$object['id']]);}}
    if($r){abort_unless($r->is_live===(bool)config('registration.live'),422);$session=app(StripeGateway::class)->session($r->stripe_session_id);
     abort_unless(($session['client_reference_id']??null)===$r->public_id&&($session['metadata']['registration']??null)===$r->public_id&&($session['amount_total']??null)===1000&&($session['currency']??null)==='eur'&&($session['livemode']??null)===(bool)config('registration.live'),422);
     if(($session['payment_status']??null)==='paid'){
      $intent=$session['payment_intent']??null;$intentId=is_array($intent)?($intent['id']??null):$intent;abort_unless(is_string($intentId)&&str_starts_with($intentId,'pi_'),422);
      $balance=is_array($intent)?($intent['latest_charge']['balance_transaction']??null):null;
      $fee=is_array($balance)&&($balance['currency']??null)==='eur'&&is_int($balance['fee']??null)?$balance['fee']:null;
      $changes=['stripe_payment_intent'=>$intentId,'fee_cents'=>$fee,'invoice_reference'=>is_string($session['invoice']??null)?$session['invoice']:$r->invoice_reference];
      if($r->payment_status==='pending')$changes+=['payment_status'=>'paid','paid_at'=>now()];
      $r->update($changes);
     }
    }
   }elseif(in_array($type,['charge.refunded','charge.dispute.created'],true)){
    $reference=$object['payment_intent']??null;$registration=$object['metadata']['registration']??null;
    $r=CandidateRegistration::where(function($query)use($reference,$registration){$query->where('stripe_payment_intent',$reference?:'__none__');if($registration)$query->orWhere('public_id',$registration);})->lockForUpdate()->first();
    if($r){$refunded=$type==='charge.refunded'?(int)($object['amount_refunded']??0):$r->refunded_cents;abort_unless($refunded>=0&&$refunded<=1000,422);$r->update(['payment_status'=>$type==='charge.refunded'&&$refunded===1000?'refunded':'review','refunded_cents'=>max($refunded,$r->refunded_cents)]);}
   }
   DB::table('registration_webhooks')->insert(['provider_event_id'=>$event['id'],'type'=>$type,'processed_at'=>now()]);
  },3);
 }
 public function refresh(CandidateRegistration $r): void {abort_unless(auth('admin')->user()?->is_active,403);if(!$r->stripe_session_id)return;$this->webhook(['id'=>'reconcile_'.(string)\Illuminate\Support\Str::uuid(),'type'=>'checkout.session.completed','data'=>['object'=>['id'=>$r->stripe_session_id]]]);}
 public function requestRefund(CandidateRegistration $registration): void {
  abort_unless(auth('admin')->user()?->is_active,403);
  DB::transaction(function()use($registration){$r=CandidateRegistration::lockForUpdate()->findOrFail($registration->id);if($r->ticket)throw ValidationException::withMessages(['refund'=>'Utiliser le remboursement du billet lié.']);if(!$r->isPaid()||$r->drinkCredit?->redeemed_cents>0)throw ValidationException::withMessages(['refund'=>'Vérifier le paiement et les tickets déjà consommés ; ce cas nécessite un traitement individuel.']);app(StripeGateway::class)->refund($r);$r->update(['payment_status'=>'review']);},3);
 }
}
