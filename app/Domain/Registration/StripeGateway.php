<?php
namespace App\Domain\Registration;
use App\Models\CandidateRegistration;
use Illuminate\Support\Facades\{Http,URL};
use Illuminate\Validation\ValidationException;
final class StripeGateway {
 private function client(){return Http::withToken(config('registration.stripe_secret'))->withHeaders(['Stripe-Version'=>config('registration.stripe_api_version')])->asForm()->connectTimeout(5)->timeout(20)->retry(2,200);}
 public function session(string $id): array {if(!preg_match('/^cs_[A-Za-z0-9_]+$/',$id))throw ValidationException::withMessages(['payment'=>'Identifiant de paiement invalide.']);return $this->client()->get('https://api.stripe.com/v1/checkout/sessions/'.$id,['expand'=>['payment_intent.latest_charge.balance_transaction']])->throw()->json();}
 public function create(CandidateRegistration $r): array {
  $campaign=$r->campaign;
  if($r->vat_basis_points>0){$rate=$this->client()->get('https://api.stripe.com/v1/tax_rates/'.$campaign->stripe_tax_rate_id)->throw()->json();if(!($rate['active']??false)||!($rate['inclusive']??false)||(int)round(($rate['percentage']??-1)*100)!==$r->vat_basis_points)throw ValidationException::withMessages(['payment'=>'Le taux Stripe doit correspondre à l’IVA inclusive validée.']);}
  $return=URL::temporarySignedRoute('registration.status',$r->accepted_at->copy()->addDays(30),['registration'=>$r->public_id]);
  $data=['mode'=>'payment','payment_method_types'=>['card'],'customer_email'=>$r->interest->email,'client_reference_id'=>$r->public_id,'success_url'=>$return,'cancel_url'=>$return,'customer_creation'=>'always','billing_address_collection'=>'required','invoice_creation'=>['enabled'=>'true'],'metadata'=>['registration'=>$r->public_id,'attempt'=>(string)$r->checkout_attempt],'payment_intent_data'=>['metadata'=>['registration'=>$r->public_id]],'line_items'=>[['quantity'=>1,'price_data'=>['currency'=>'eur','unit_amount'=>1000,'tax_behavior'=>'inclusive','product_data'=>['name'=>'Os Jogos do Agricultor · candidatura / candidature','description'=>'10 EUR TTC por pessoa / par personne. Não garante seleção / ne garantit pas la sélection.']]]]];
  if($r->vat_basis_points>0)$data['line_items'][0]['tax_rates']=[$campaign->stripe_tax_rate_id];
  return $this->client()->withHeaders(['Idempotency-Key'=>'registration-'.$r->public_id.'-'.$r->checkout_attempt])->post('https://api.stripe.com/v1/checkout/sessions',$data)->throw()->json();
 }
 public function checkoutUrl(array $session): string {$url=$session['url']??'';if(parse_url($url,PHP_URL_SCHEME)!=='https'||parse_url($url,PHP_URL_HOST)!=='checkout.stripe.com')throw ValidationException::withMessages(['payment'=>'Adresse du prestataire invalide.']);return $url;}
 public function refund(CandidateRegistration $r): array {return $this->client()->withHeaders(['Idempotency-Key'=>'registration-refund-'.$r->public_id])->post('https://api.stripe.com/v1/refunds',['payment_intent'=>$r->stripe_payment_intent,'amount'=>$r->amount_cents])->throw()->json();}
 public function verifyWebhook(string $payload,string $header): array {
  $secret=(string)config('registration.webhook_secret');$timestamp=null;$signatures=[];
  foreach(explode(',',$header) as $part){[$key,$value]=array_pad(explode('=',trim($part),2),2,'');if($key==='t'&&ctype_digit($value))$timestamp=(int)$value;if($key==='v1')$signatures[]=$value;}
  abort_unless($secret!==''&&$timestamp&&abs(time()-$timestamp)<=300,400);
  $expected=hash_hmac('sha256',$timestamp.'.'.$payload,$secret);abort_unless(collect($signatures)->contains(fn($signature)=>hash_equals($expected,$signature)),400);
  $event=json_decode($payload,true,64);abort_unless(is_array($event)&&filled($event['id']??null)&&($event['livemode']??null)===(bool)config('registration.live'),400);return $event;
 }
}
