<?php
namespace App\Domain\Tickets;
use App\Models\EventTicket;
use App\Domain\Registration\StripeGateway;
use Illuminate\Support\Facades\{DB,Http,URL};
use Illuminate\Validation\ValidationException;
final class TicketPayments {
 private function client(){return Http::withToken(config('registration.stripe_secret'))->withHeaders(['Stripe-Version'=>config('registration.stripe_api_version')])->asForm()->connectTimeout(5)->timeout(20)->retry(2,200);}
 public function configured(): bool {return config('registration.enabled')&&filled(config('registration.webhook_secret'))&&str_starts_with((string)config('registration.stripe_secret'),config('registration.live')?'sk_live_':'sk_test_');}
 public function checkout(EventTicket $ticket): string {return DB::transaction(function()use($ticket){
  $t=EventTicket::lockForUpdate()->findOrFail($ticket->id);$p=$t->plan;
  if(!$this->configured()||!$p->is_open||$p->blockers()||$t->terms_version!==$p->terms_version||$t->channel!=='card'||$t->status!=='pending'||$t->is_live!==(bool)config('registration.live'))throw ValidationException::withMessages(['ticket'=>'Paiement fermé ou à vérifier.']);
  $g=app(StripeGateway::class);if($t->stripe_session_id){$existing=$g->session($t->stripe_session_id);if(($existing['status']??null)==='open')return $g->checkoutUrl($existing);if(($existing['status']??null)!=='expired')throw ValidationException::withMessages(['ticket'=>'Confirmation en cours.']);$t->checkout_attempt++;}
  if($t->vat_basis_points>0){if(!preg_match('/^txr_[A-Za-z0-9]+$/',(string)$p->stripe_tax_rate_id))throw ValidationException::withMessages(['ticket'=>'Taux Stripe requis.']);$rate=$this->client()->get('https://api.stripe.com/v1/tax_rates/'.$p->stripe_tax_rate_id)->throw()->json();if(!($rate['active']??false)||!($rate['inclusive']??false)||(int)round(($rate['percentage']??-1)*100)!==$t->vat_basis_points)throw ValidationException::withMessages(['ticket'=>'Le taux IVA inclusif ne correspond pas.']);}
  $return=URL::temporarySignedRoute('ticket.owner',now()->addDays(120),['ticket'=>$t->public_id]);
  $data=['mode'=>'payment','payment_method_types'=>['card'],'client_reference_id'=>$t->public_id,'success_url'=>$return,'cancel_url'=>$return,'customer_creation'=>'always','billing_address_collection'=>'required','invoice_creation'=>['enabled'=>'true'],'metadata'=>['ticket'=>$t->public_id,'attempt'=>(string)$t->checkout_attempt],'payment_intent_data'=>['metadata'=>['ticket'=>$t->public_id]],'line_items'=>[['quantity'=>1,'price_data'=>['currency'=>'eur','unit_amount'=>$t->amount_cents,'tax_behavior'=>'inclusive','product_data'=>['name'=>'Os Jogos do Agricultor · '.$t->kind,'description'=>'Pré-venda / prévente · '.$p->terms_version]]]]];if($t->email)$data['customer_email']=$t->email;if($t->vat_basis_points>0)$data['line_items'][0]['tax_rates']=[$p->stripe_tax_rate_id];
  $session=$this->client()->withHeaders(['Idempotency-Key'=>'ticket-'.$t->public_id.'-'.$t->checkout_attempt])->post('https://api.stripe.com/v1/checkout/sessions',$data)->throw()->json();$url=$g->checkoutUrl($session);$t->update(['stripe_session_id'=>$session['id'],'checkout_url'=>$url]);return $url;
 },3);}
 public function webhook(array $event): void {DB::transaction(function()use($event){
  if(DB::table('ticket_webhooks')->where('provider_event_id',$event['id'])->exists())return;$o=$event['data']['object']??[];$type=$event['type']??'';
  if(in_array($type,['checkout.session.completed','checkout.session.async_payment_succeeded'],true)){
   $t=EventTicket::where('stripe_session_id',$o['id']??'__none__')->lockForUpdate()->first();
   if(!$t&&filled($o['metadata']['ticket']??null)){$t=EventTicket::where('public_id',$o['metadata']['ticket'])->lockForUpdate()->first();if($t)abort_unless(!$t->stripe_session_id,409);}
   if($t){$s=app(StripeGateway::class)->session($o['id']);abort_unless($t->channel==='card'&&($s['client_reference_id']??null)===$t->public_id&&($s['metadata']['ticket']??null)===$t->public_id&&(string)($s['metadata']['attempt']??'')===(string)$t->checkout_attempt&&($s['amount_total']??null)===$t->amount_cents&&($s['currency']??null)==='eur'&&($s['livemode']??null)===$t->is_live&&$t->is_live===(bool)config('registration.live'),422);
    if(!$t->stripe_session_id)$t->update(['stripe_session_id'=>$o['id']]);
    if(($s['payment_status']??null)==='paid'){$intent=$s['payment_intent']??null;$id=is_array($intent)?($intent['id']??null):$intent;abort_unless(is_string($id)&&str_starts_with($id,'pi_'),422);$balance=is_array($intent)?($intent['latest_charge']['balance_transaction']??null):null;$fee=is_array($balance)&&($balance['currency']??null)==='eur'&&is_int($balance['fee']??null)?$balance['fee']:null;$t->update(['stripe_payment_intent'=>$id,'fee_cents'=>$fee]);app(Ticketing::class)->confirm($t);}
   }
  }elseif(in_array($type,['charge.refunded','charge.dispute.created'],true)){
   $t=EventTicket::where(function($q)use($o){$q->where('stripe_payment_intent',$o['payment_intent']??'__none__');if(filled($o['metadata']['ticket']??null))$q->orWhere('public_id',$o['metadata']['ticket']);})->lockForUpdate()->first();
   if($t){abort_unless($t->is_live===(bool)config('registration.live'),422);$amount=$type==='charge.refunded'?(int)($o['amount_refunded']??0):0;abort_unless($amount>=0&&$amount<=$t->amount_cents,422);$state=$type==='charge.refunded'&&$amount===$t->amount_cents?'refunded':'review';$t->update(['status'=>$state,'public_listing'=>false]);if($t->candidate_registration_id)$t->candidate->update(['payment_status'=>$state,'refunded_cents'=>max($amount,$t->candidate->refunded_cents)]);}
  }
  DB::table('ticket_webhooks')->insert(['provider_event_id'=>$event['id'],'processed_at'=>now()]);
 },3);}
 public function refresh(EventTicket $t): void {abort_unless(auth('admin')->user()?->is_active,403);if($t->stripe_session_id)$this->webhook(['id'=>'reconcile_'.\Illuminate\Support\Str::uuid(),'type'=>'checkout.session.completed','data'=>['object'=>['id'=>$t->stripe_session_id]]]);}
 public function refund(EventTicket $ticket): void {abort_unless(auth('admin')->user()?->is_active,403);DB::transaction(function()use($ticket){$t=EventTicket::lockForUpdate()->findOrFail($ticket->id);if(!$t->valid()||$t->channel!=='card'||!$t->stripe_payment_intent||$t->redeemed_at||$t->candidate?->drinkCredit?->redeemed_cents>0)throw ValidationException::withMessages(['ticket'=>'Vérifier paiement et consommations avant remboursement.']);$this->client()->withHeaders(['Idempotency-Key'=>'ticket-refund-'.$t->public_id])->post('https://api.stripe.com/v1/refunds',['payment_intent'=>$t->stripe_payment_intent,'amount'=>$t->amount_cents])->throw();$t->update(['status'=>'review']);$t->candidate?->update(['payment_status'=>'review']);},3);}
}
