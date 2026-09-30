<?php
namespace App\Domain\Tickets;
use App\Models\{PresalePlan,Distributor,EventTicket,TicketSettlement,CandidateRegistration};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Domain\Finance\Money;
final class Ticketing {
 public function blockers(PresalePlan $p): array {
  $b=[];if(!config('presales.enabled'))$b[]='Préventes désactivées dans la configuration';
  if(!$p->eventProject?->is_public||!config('events.privacy_ready')||blank(config('events.organizer_name'))||!filter_var(config('events.contact_email'),FILTER_VALIDATE_EMAIL))$b[]='Édition publique, organisateur et confidentialité requis';
  foreach(['terms_fr','terms_pt','refund_fr','refund_pt','split_evidence','billing_procedure','approval_evidence'] as $field)if(blank($p->$field))$b[]=$field.' à valider';
  if($p->vat_basis_points===null)$b[]='IVA à confirmer';if(!$p->capacity)$b[]='Contingent requis';if(!$p->closes_at||$p->closes_at->isPast())$b[]='Échéance future requise';
  if(config('registration.live')&&!str_starts_with(config('app.url'),'https://'))$b[]='Adresse HTTPS officielle requise';
  if($p->eventProject){$b=array_merge($b,app(\App\Domain\Planning\Readiness::class)->blockers($p->eventProject,'registration'));}
  return array_values(array_unique($b));
 }
 private function fail(string $message): never {throw ValidationException::withMessages(['ticket'=>$message]);}
 public function issue(PresalePlan $plan,array $data,?Distributor $distributor=null): EventTicket {
  return DB::transaction(function()use($plan,$data,$distributor){
   $p=PresalePlan::lockForUpdate()->findOrFail($plan->id);if(filled($data['request_id']??null)&&($existing=$p->tickets()->where('request_id',$data['request_id'])->first())){if($existing->distributor_id!==$distributor?->id)$this->fail('Requête déjà utilisée.');return $existing;}if(!$p->is_open||$p->blockers())$this->fail('Préventes fermées ou conditions à valider.');
   if(($data['terms_version']??null)!==$p->terms_version)$this->fail('Les conditions ont changé : relire la proposition.');
   $d=$distributor?Distributor::lockForUpdate()->findOrFail($distributor->id):null;
   if($d&&(!$d->active()||$d->standPartner->stand->event_project_id!==$p->event_project_id))$this->fail('Distributeur non habilité pour cette édition.');
   $cash=($data['channel']??'card')==='cash';if($cash&&(!$d||auth('relay')->id()!==$d->id))$this->fail('Seul le relais connecté peut enregistrer sa collecte.');
   $stand=$d?->standPartner?->stand;if(!$stand&&filled($data['stand_id']??null))$stand=\App\Models\Stand::whereKey($data['stand_id'])->where('event_project_id',$p->event_project_id)->where('kind','village')->where('is_public',true)->firstOrFail();
   if(!$stand||blank($stand->freguesia))$this->fail('Choisir une freguesia participante et son stand.');
   if($p->tickets()->where('is_live',(bool)config('registration.live'))->whereNotIn('status',['cancelled','refunded'])->count()>=$p->capacity)$this->fail('Contingent épuisé. Les paiements en attente occupent aussi une place.');
   $candidate=null;$kind=$p->kind;
   if(($data['candidate']??false)){
    $campaign=\App\Models\RegistrationCampaign::where('event_project_id',$p->event_project_id)->lockForUpdate()->first();if(!$campaign||!$campaign->is_open||$campaign->closes_at?->isPast()||$campaign->vote_closed_at||$campaign->blockers())$this->fail('Campagne de candidature fermée.');
    if($p->price_cents!==1000||$campaign->vat_basis_points!==$p->vat_basis_points)$this->fail('Aligner les deux campagnes : candidature unique à 10 € TTC et même IVA.');
    if(blank($campaign->refund_policy_fr)||blank($campaign->refund_policy_pt)||blank($campaign->validation_evidence)||blank($campaign->billing_procedure)||blank($campaign->terms_fr)||blank($campaign->terms_pt))$this->fail('Conditions de candidature incomplètes.');
    if(($data['candidate_terms_version']??null)!==$campaign->terms_version)$this->fail('Relire les conditions de candidature.');
    $email=mb_strtolower(trim($data['email']??''));if(!$email)$this->fail('Email requis pour une candidature.');
    if($campaign->registrations()->where('email_key',$email)->exists())$this->fail('Une candidature existe pour cet email ; ne pas encaisser une seconde fois.');
    if(empty($data['expert_roles']))$this->fail('Choisir au moins un rôle.');
    $interest=$p->eventProject->interests()->create(['profile'=>'team','name'=>$data['buyer_name'],'email'=>$email,'freguesia'=>$stand->freguesia,'expert_roles'=>$data['expert_roles'],'privacy_acknowledged_at'=>now(),'privacy_version'=>'presale-v1','source'=>$d?'relay':'direct']);
    $candidate=$campaign->registrations()->create(['interest_id'=>$interest->id,'email_key'=>$email,'is_live'=>(bool)config('registration.live'),'vat_basis_points'=>$p->vat_basis_points,'terms_version'=>$campaign->terms_version,'terms_snapshot'=>$campaign->terms_fr."\n\n".$campaign->terms_pt."\n\n".$campaign->refund_policy_fr."\n\n".$campaign->refund_policy_pt,'accepted_at'=>now()]);$kind='candidate';
   }
   $parts=[];foreach(['parking','relay','junta','village'] as $part)$parts[$part.'_cents']=intdiv($p->price_cents*$p->{$part.'_basis_points'},10000);
   return $p->tickets()->create($parts+['request_id'=>$data['request_id']??null,'distributor_id'=>$d?->id,'stand_id'=>$stand->id,'candidate_registration_id'=>$candidate?->id,'kind'=>$kind,'buyer_name'=>$data['buyer_name'],'email'=>$data['email']??null,'phone'=>$data['phone'],'freguesia'=>$stand->freguesia,'public_listing'=>(bool)($data['public_listing']??false),'public_name'=>($data['public_listing']??false)?$data['public_name']:null,'listing_consented_at'=>($data['public_listing']??false)?now():null,'terms_version'=>$p->terms_version,'terms_snapshot'=>$p->terms_fr."\n\n".$p->terms_pt."\n\n".$p->refund_fr."\n\n".$p->refund_pt."\nRépartition / repartição : ".collect($parts)->map(fn($value,$key)=>$key.' = '.Money::format($value))->implode(' ; '),'accepted_at'=>now(),'is_live'=>(bool)config('registration.live'),'channel'=>$cash?'cash':'card','status'=>'pending','amount_cents'=>$p->price_cents,'vat_basis_points'=>$p->vat_basis_points,'commission_retained_cents'=>$cash?$parts['relay_cents']:0,'cash_collected_at'=>$cash?now():null]);
  },3);
 }
 public function settle(Distributor $distributor,array $ids,int $received,string $reference,string $evidence,\Carbon\CarbonInterface $receivedAt): TicketSettlement {
  abort_unless(auth('admin')->user()?->is_active,403);
  return DB::transaction(function()use($distributor,$ids,$received,$reference,$evidence,$receivedAt){
   if(!$ids||count($ids)!==count(array_unique($ids))||count($ids)>500||blank($reference)||blank($evidence)||$receivedAt->isFuture())$this->fail('Sélection, référence, preuve et date réelle de réception requises.');
   $d=Distributor::lockForUpdate()->findOrFail($distributor->id);$tickets=$d->tickets()->whereIn('id',$ids)->orderBy('id')->lockForUpdate()->get();
   if($tickets->count()!==count($ids)||$tickets->pluck('presale_plan_id')->unique()->count()!==1)$this->fail('Les billets doivent appartenir au même relais et à la même campagne.');
   foreach($tickets as $t)if($t->status!=='pending'||$t->channel!=='cash'||!$t->cash_collected_at||$t->is_live!==(bool)config('registration.live')||$t->ticket_settlement_id||$receivedAt->lt($t->cash_collected_at))$this->fail('Billet déjà rapproché ou incompatible.');
   if($received!==$tickets->sum(fn($t)=>$t->expectedRemittance()))$this->fail('Le montant reçu doit égaler les billets bruts moins les commissions déjà retenues.');
   $s=TicketSettlement::create(['presale_plan_id'=>$tickets->first()->presale_plan_id,'distributor_id'=>$d->id,'is_live'=>(bool)config('registration.live'),'received_cents'=>$received,'reference'=>trim($reference),'evidence'=>$evidence,'received_at'=>$receivedAt,'admin_id'=>auth('admin')->id()]);
   foreach($tickets as $t){$t->update(['ticket_settlement_id'=>$s->id,'fee_cents'=>0]);$this->confirm($t,$receivedAt);}return $s;
  },3);
 }
 public function confirm(EventTicket $t,$at=null): void {
  // Internal transition: caller holds the ticket lock and has verified provider settlement or cash receipt.
  if($t->status!=='pending')return;$receivedAt=$at?:now();$state='paid';$c=$t->candidate_registration_id?CandidateRegistration::lockForUpdate()->findOrFail($t->candidate_registration_id):null;if($c){if($c->payment_status!=='pending')$this->fail('Candidature déjà payée ou en revue.');if($c->campaign->vote_closed_at||!$c->campaign->closes_at||$receivedAt->gt($c->campaign->closes_at))$state='review';}
  $t->update(['status'=>$state,'paid_at'=>$receivedAt]);$c?->update(['payment_status'=>$state,'paid_at'=>$receivedAt,'fee_cents'=>$t->fee_cents]);
  if($state==='paid')$t->messages()->firstOrCreate(['purpose'=>'confirmation']);
 }
 public function redeem(EventTicket $ticket,string $evidence): void {abort_unless(auth('admin')->user()?->is_active,403);DB::transaction(function()use($ticket,$evidence){$t=EventTicket::lockForUpdate()->findOrFail($ticket->id);if(!$t->valid()||$t->kind!=='admission'||blank($evidence))$this->fail('Seul un billet d’entrée payé et vérifié peut être contrôlé.');if($t->redeemed_at)$this->fail('Billet déjà utilisé : ne pas laisser entrer une deuxième fois.');$t->update(['redeemed_at'=>now(),'redeemed_by'=>auth('admin')->id(),'redemption_evidence'=>$evidence]);},3);}
 public function refundCash(EventTicket $ticket,string $evidence,int $commissionRecovered=0): void {abort_unless(auth('admin')->user()?->is_active,403);DB::transaction(function()use($ticket,$evidence,$commissionRecovered){$t=EventTicket::lockForUpdate()->findOrFail($ticket->id);if($commissionRecovered<0||$commissionRecovered>$t->commission_retained_cents)$this->fail('Commission récupérée invalide.');if(!in_array($t->status,['paid','review'])||!$t->paid_at||$t->is_live!==(bool)config('registration.live')||$t->channel!=='cash'||$t->redeemed_at||$t->candidate?->drinkCredit?->redeemed_cents>0||blank($evidence))$this->fail('Vérifier le remboursement intégral, la commission récupérée ou supportée par QAPAS et l’absence de consommation.');$t->update(['status'=>'refunded','refund_evidence'=>$evidence,'commission_recovered_cents'=>$commissionRecovered,'commission_recovery_evidence'=>$commissionRecovered>0?$evidence:null,'public_listing'=>false]);$t->candidate?->update(['payment_status'=>'refunded','refunded_cents'=>$t->amount_cents]);},3);}
 public function cancel(EventTicket $ticket,string $evidence): void {abort_unless(auth('admin')->user()?->is_active,403);DB::transaction(function()use($ticket,$evidence){$t=EventTicket::lockForUpdate()->findOrFail($ticket->id);if($t->status!=='pending'||$t->channel!=='cash'||blank($evidence))$this->fail('Documenter la restitution du liquide au client avant annulation d’un reçu en attente.');$t->update(['status'=>'cancelled','refund_evidence'=>$evidence]);},3);}
 public function commissionPaid(EventTicket $ticket,string $evidence): void {abort_unless(auth('admin')->user()?->is_active,403);DB::transaction(function()use($ticket,$evidence){$t=EventTicket::lockForUpdate()->findOrFail($ticket->id);if(!$t->valid()||$t->channel!=='card'||!$t->distributor_id||$t->commission_paid_at||blank($evidence))$this->fail('Commission carte due, non encore réglée, et preuve de paiement requises.');$t->update(['commission_paid_at'=>now(),'commission_payment_evidence'=>$evidence]);},3);}
 public function commissionRecovered(EventTicket $ticket,int $amount,string $evidence): void {abort_unless(auth('admin')->user()?->is_active,403);DB::transaction(function()use($ticket,$amount,$evidence){$t=EventTicket::lockForUpdate()->findOrFail($ticket->id);if(!in_array($t->status,['refunded','review'])||$amount<$t->commission_recovered_cents||$amount>$t->paidCommissionCents()||blank($evidence))$this->fail('Récupération cumulée documentée, au plus la commission réellement payée.');$t->update(['commission_recovered_cents'=>$amount,'commission_recovery_evidence'=>$evidence]);},3);}
 public function report(PresalePlan $p): array {
  $r=['review_received_cents'=>0,'commission_card_due_cents'=>0,'refund_commission_loss_cents'=>0,'gross_cents'=>0,'pending_remittance_cents'=>0,'commission_retained_cents'=>0,'parking_cents'=>0,'relay_cents'=>0,'junta_cents'=>0,'village_cents'=>0,'fees_cents'=>0,'fees_pending'=>0,'qapas_before_vat_costs_cents'=>0,'net_before_delivery_cents'=>0,'candidate_credit_face_cents'=>0,'available_cents'=>0,'paid_count'=>0];
  foreach($p->tickets()->with('candidate.drinkCredit')->where('is_live',(bool)config('registration.live'))->get() as $t){if($t->status==='review'&&$t->paid_at)$r['review_received_cents']+=$t->amount_cents;if($t->status==='pending'&&$t->channel==='cash')$r['pending_remittance_cents']+=$t->expectedRemittance();if(in_array($t->status,['refunded','review']))$r['refund_commission_loss_cents']+=max(0,$t->paidCommissionCents()-$t->commission_recovered_cents);if(!$t->valid())continue;if($t->channel==='card'&&$t->distributor_id&&!$t->commission_paid_at)$r['commission_card_due_cents']+=$t->relay_cents;$r['paid_count']++;$r['gross_cents']+=$t->amount_cents;foreach(['parking','relay','junta','village','commission_retained'] as $k)$r[$k.'_cents']+=$t->{$k.'_cents'};$r['qapas_before_vat_costs_cents']+=$t->amount_cents-$t->assignedCents();if($t->fee_cents===null)$r['fees_pending']++;else{$r['fees_cents']+=$t->fee_cents;$r['net_before_delivery_cents']+=Money::net($t->amount_cents,$t->vat_basis_points)-$t->assignedCents()-$t->fee_cents;}if($t->candidate&&$t->candidate->decision!=='selected')$r['candidate_credit_face_cents']+=max(0,$t->candidate->credit_cents-($t->candidate->drinkCredit?->redeemed_cents??0));}
  $r['net_before_delivery_cents']-=$r['refund_commission_loss_cents'];
  if($p->bank_available_cents!==null&&$p->refund_reserve_cents!==null&&filled($p->bank_evidence))$r['available_cents']=max(0,min($r['net_before_delivery_cents'],$p->bank_available_cents)-$p->refund_reserve_cents-$r['candidate_credit_face_cents']);return $r;
 }
}
