<?php
namespace Tests\Feature;
use App\Models\{Admin,EventProject,RegistrationCampaign,CandidateRegistration,DrinkCredit,Team,Scenario};
use App\Domain\Teams\ExpertRoles;
use App\Domain\Registration\{RegistrationPayments,RegistrationDecision,StripeGateway};
use App\Domain\Finance\UnitCosting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http,URL,DB};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class ExpertRegistrationTest extends TestCase {
 use RefreshDatabase;
 private function admin(): Admin {$a=Admin::firstOrCreate(['email'=>'registration@example.test'],['name'=>'Admin','password'=>'long-testing-password']);$this->actingAs($a,'admin');return $a;}
 private function campaign(bool $open=true): RegistrationCampaign {
  $this->admin();config(['events.privacy_ready'=>true,'events.organizer_name'=>'Organizer','events.contact_email'=>'test@example.test','registration.enabled'=>true,'registration.live'=>false,'registration.stripe_secret'=>'sk_test_fixture','registration.webhook_secret'=>'whsec_fixture']);
  $p=EventProject::create(['name'=>'Edition','slug'=>'registration-test','is_public'=>true,'venue'=>'Contracted site','capacity'=>300,'starts_at'=>now()->addDays(60),'ends_at'=>now()->addDays(61)]);
  foreach($p->requirements as $q)$q->update(['status'=>'approved','evidence'=>'Reviewed file','reviewed_by'=>'Responsible reviewer','reviewed_at'=>now()]);
  return RegistrationCampaign::create(['event_project_id'=>$p->id,'name'=>'Experts','terms_version'=>'v1','terms_fr'=>'Conditions acceptées','terms_pt'=>'Condições aceites','refund_policy_fr'=>'Remboursement selon contrat','refund_policy_pt'=>'Reembolso conforme contrato','vat_basis_points'=>0,'billing_procedure'=>'Reviewed invoice workflow','validation_evidence'=>'Reviewed terms and tax','closes_at'=>now()->addDays(10),'is_open'=>$open]);
 }
 private function registration(RegistrationCampaign $c,string $email='ana@example.test',bool $paid=false): CandidateRegistration {
  $i=$c->eventProject->interests()->create(['profile'=>'team','name'=>'Ana','email'=>$email,'freguesia'=>'Belmonte','expert_roles'=>ExpertRoles::CODES,'privacy_acknowledged_at'=>now(),'privacy_version'=>'test']);
  return $c->registrations()->create(['interest_id'=>$i->id,'email_key'=>$email,'amount_cents'=>1000,'credit_cents'=>1000,'currency'=>'eur','vat_basis_points'=>0,'terms_version'=>'v1','terms_snapshot'=>'Accepted terms','accepted_at'=>now(),'payment_status'=>$paid?'paid':'pending','paid_at'=>$paid?now():null,'fee_cents'=>$paid?40:null]);
 }
 private function stripeSessionFixture(CandidateRegistration $r,array $extra=[]): array {return array_replace(['id'=>'cs_test_example','status'=>'complete','payment_status'=>'paid','amount_total'=>1000,'currency'=>'eur','livemode'=>false,'client_reference_id'=>$r->public_id,'metadata'=>['registration'=>$r->public_id],'payment_intent'=>['id'=>'pi_example','latest_charge'=>['balance_transaction'=>['fee'=>40,'currency'=>'eur']]],'invoice'=>'in_example'],$extra);}
 private function webhook(array $event,?string $signature=null){$payload=json_encode($event);$time=time();$sig=$signature??'t='.$time.',v1='.hash_hmac('sha256',$time.'.'.$payload,'whsec_fixture');return $this->call('POST','/payments/stripe/webhook',[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_STRIPE_SIGNATURE'=>$sig],$payload);}
 private function closeVote(RegistrationCampaign $c): void {$this->travel(11)->days();$c->update(['vote_closed_at'=>now(),'vote_minutes_reference'=>'Signed local vote minutes']);}
 public function test_seed_archives_douglas_and_has_no_fabrication_or_included_furniture_in_current_scenario(): void {
  $this->seed();$s=Scenario::where('template_key','costing-rental-experts-v1')->firstOrFail();$r=app(UnitCosting::class)->calculate($s);
  $this->assertTrue($s->furniture_paid_by_participant);$this->assertSame(1062904,$r['gross_cost_cents']);$this->assertSame(-475720,$r['report']['forecast_margin_cents']);$this->assertFalse($r['report']['launch_ready']);
  $this->assertFalse($s->budgetLines()->where('costing_key','furniture-manufacture')->exists());$this->assertSame(0,$s->includedStands->sum('included_furniture_sets'));$this->assertTrue(Scenario::where('template_key','costing-two-days-v1')->first()->is_archived);
  $stand=$s->includedStands->first();$stand->update(['furniture_unit_gross_cents'=>2000,'furniture_delivery_cents'=>1000,'furniture_deposit_cents'=>5000,'furniture_evidence'=>'Rental quote']);$stand->update(['furniture_confirmed'=>true]);$this->assertSame(5000,$stand->furnitureBudget()['gross_cents']);$this->assertTrue($stand->furnitureBudget()['confirmed']);
  $this->seed();$this->assertSame(2000,$stand->fresh()->furniture_unit_gross_cents);$this->assertDatabaseCount('registration_campaigns',1);$this->assertFalse(RegistrationCampaign::first()->is_open);
  $this->get('/events/os-jogos-do-agricultor')->assertOk()->assertSee('10 €')->assertDontSee('450 €/m³');
  $this->withSession(['locale'=>'pt'])->get('/events/os-jogos-do-agricultor/candidature')->assertOk()->assertSee('10 € com IVA');
 }
 public function test_thirteen_roles_require_review_and_documented_cumulation_before_election(): void {
  $this->admin();$p=EventProject::create(['name'=>'Village','slug'=>'village']);$team=$p->teams()->create(['name'=>'Team','freguesia'=>'Belmonte']);$this->assertCount(13,$team->roleAssignments);ExpertRoles::seedSlots($team);$this->assertCount(13,$team->fresh()->roleAssignments);
  foreach($team->roleAssignments as $role)$role->update(['candidate_name'=>'Expert','candidate_reference'=>'register-1','consent_confirmed'=>true,'competence_evidence'=>'Skill checked','status'=>'confirmed']);
  $this->assertSame(13,$team->composition()['covered']);$this->assertFalse($team->composition()['complete']);$team->update(['role_cumulation_evidence'=>'All timetables and responsibilities checked','election_minutes'=>'Signed local ballot minutes']);$team->update(['status'=>'elected']);$this->assertTrue($team->composition()['complete']);
  $team->roleAssignments->first()->update(['candidate_name'=>'Replacement']);$this->assertFalse($team->fresh()->composition()['complete']);$this->assertNull($team->fresh()->role_cumulation_evidence);
 }
 public function test_paid_campaign_blocks_unpaid_role_confirmation(): void {
  $c=$this->campaign();$r=$this->registration($c);$team=$c->eventProject->teams()->create(['name'=>'Team','freguesia'=>'Belmonte']);
  $this->expectException(ValidationException::class);$team->roleAssignments()->first()->update(['interest_id'=>$r->interest_id,'candidate_name'=>'Ana','candidate_reference'=>'local-register-1','consent_confirmed'=>true,'competence_evidence'=>'Verified','status'=>'confirmed']);
 }
 public function test_public_registration_is_fixed_price_multiple_roles_one_fee_and_private_status_does_not_mark_paid(): void {
  $c=$this->campaign();$data=['name'=>'Ana','email'=>'ANA@example.test','freguesia'=>'Belmonte','expert_roles'=>['cook','leader'],'terms_version'=>'v1','terms'=>1,'privacy'=>1,'amount_cents'=>1,'payment_status'=>'paid'];
  $response=$this->post('/events/registration-test/candidature',$data)->assertRedirect();$r=CandidateRegistration::firstOrFail();$this->assertSame(1000,$r->amount_cents);$this->assertSame('pending',$r->payment_status);$this->assertSame(['cook','leader'],$r->interest->expert_roles);$this->assertFalse($r->interest->marketing_opt_in);
  $this->get($response->headers->get('Location'))->assertOk()->assertSee('pas encore confirmé');$this->assertFalse($r->fresh()->isPaid());$this->get('/candidatures/'.$r->public_id)->assertForbidden();
  $this->post('/events/registration-test/candidature',$data)->assertRedirect();$this->assertDatabaseCount('candidate_registrations',1);
  $this->flushSession();$this->post('/events/registration-test/candidature',$data)->assertSessionHasErrors('email');$this->assertDatabaseCount('candidate_registrations',1);
 }
 public function test_closed_campaign_and_unaccepted_terms_cannot_take_payment(): void {
  $c=$this->campaign(false);$this->get('/events/registration-test/candidature')->assertOk()->assertSee('pas encore ouvertes');
  $data=['name'=>'Ana','email'=>'a@example.test','freguesia'=>'Belmonte','expert_roles'=>['cook'],'terms_version'=>'v1','terms'=>1,'privacy'=>1];$this->post('/events/registration-test/candidature',$data)->assertStatus(409);
  $c->update(['is_open'=>true]);unset($data['terms']);$this->post('/events/registration-test/candidature',$data)->assertSessionHasErrors('terms');$this->assertDatabaseCount('candidate_registrations',0);
 }
 public function test_checkout_uses_provider_host_exact_amount_and_idempotent_reuse(): void {
  $c=$this->campaign();$r=$this->registration($c);Http::preventStrayRequests();Http::fake(['api.stripe.com/v1/checkout/sessions*'=>Http::response(['id'=>'cs_test_example','status'=>'open','url'=>'https://checkout.stripe.com/c/pay/example'])]);
  $service=app(RegistrationPayments::class);$this->assertSame('https://checkout.stripe.com/c/pay/example',$service->checkout($r));$service->checkout($r->fresh());
  Http::assertSent(fn($request)=>$request->method()==='POST'&&(int)$request['line_items'][0]['price_data']['unit_amount']===1000&&$request->hasHeader('Idempotency-Key','registration-'.$r->public_id.'-1'));
  $this->assertSame(1,collect(Http::recorded())->filter(fn($pair)=>$pair[0]->method()==='POST')->count());$this->assertFalse($r->fresh()->isPaid());
 }
 public function test_signed_webhook_confirms_once_and_refund_freezes_credit_even_after_late_completed_event(): void {
  $c=$this->campaign();$r=$this->registration($c);$r->update(['stripe_session_id'=>'cs_test_example']);Http::fake(['api.stripe.com/*'=>Http::response($this->stripeSessionFixture($r))]);
  $event=['id'=>'evt_complete','type'=>'checkout.session.completed','livemode'=>false,'data'=>['object'=>['id'=>'cs_test_example','metadata'=>['registration'=>$r->public_id]]]];
  $this->webhook($event,'t=1,v1=bad')->assertStatus(400);$this->assertFalse($r->fresh()->isPaid());
  $this->webhook($event)->assertOk();$this->webhook($event)->assertOk();$this->assertTrue($r->fresh()->isPaid());$this->assertSame(40,$r->fresh()->fee_cents);$this->assertDatabaseCount('registration_webhooks',1);
  $this->closeVote($c);app(RegistrationDecision::class)->record($r->fresh(),'not_selected','Signed vote: not selected');$credit=$r->fresh()->drinkCredit;$this->assertSame(1000,$credit->availableCents());
  $this->webhook(['id'=>'evt_refund','type'=>'charge.refunded','livemode'=>false,'data'=>['object'=>['payment_intent'=>'pi_example','amount_refunded'=>1000]]])->assertOk();$event['id']='evt_late';$this->webhook($event)->assertOk();$this->assertSame('refunded',$r->fresh()->payment_status);$this->assertSame(0,$credit->fresh()->availableCents());
 }
 public function test_wrong_amount_or_unpaid_session_never_validates_candidacy(): void {
  $c=$this->campaign();$r=$this->registration($c);$r->update(['stripe_session_id'=>'cs_test_example']);$event=['id'=>'evt_bad','type'=>'checkout.session.completed','livemode'=>false,'data'=>['object'=>['id'=>'cs_test_example']]];
  Http::fake(['api.stripe.com/*'=>Http::sequence()->push($this->stripeSessionFixture($r,['amount_total'=>999]))->push($this->stripeSessionFixture($r,['payment_status'=>'unpaid']))]);$this->webhook($event)->assertStatus(422);$this->assertFalse($r->fresh()->isPaid());
  $this->webhook($event)->assertOk();$this->assertFalse($r->fresh()->isPaid());
 }
 public function test_non_selection_requires_closed_vote_then_tickets_are_idempotent_and_never_a_cash_sale(): void {
  $c=$this->campaign();$r=$this->registration($c,paid:true);$service=app(RegistrationDecision::class);
  try{$service->record($r,'not_selected','Before vote should fail');$this->fail('Vote not closed');}catch(ValidationException){}
  $this->closeVote($c);$service->record($r,'not_selected','Signed local vote result');$service->record($r,'not_selected','Signed local vote result');$this->assertDatabaseCount('drink_credits',1);$credit=$r->fresh()->drinkCredit;$operation=(string)Str::uuid();
  $service->redeem($credit,300,'Three drinks',$operation);$service->redeem($credit,300,'Three drinks',$operation);$this->assertSame(700,$credit->fresh()->availableCents());$this->assertDatabaseCount('drink_redemptions',1);$this->assertDatabaseCount('budget_lines',0);
  $this->expectException(ValidationException::class);$service->redeem($credit,800,'Too much',(string)Str::uuid());
 }
 public function test_funding_requires_bank_evidence_fees_and_refund_reserve_and_detects_refund_shortfall(): void {
  $c=$this->campaign();$r=$this->registration($c,paid:true);$this->assertSame(0,$c->funding()['available_cents']);
  $c->update(['bank_available_cents'=>960,'bank_evidence'=>'Stripe payout reconciled','refund_reserve_cents'=>100]);$f=$c->funding();$this->assertSame(860,$f['available_cents']);$this->assertSame(1000,$f['potential_drink_credit_cents']);
  $c->update(['drinks_supplier'=>'Brewer','drinks_contract_reference'=>'Contract with return terms','drinks_contract_cents'=>5000,'drinks_allocated_cents'=>800,'funding_evidence'=>'Deposit for beverage procurement']);
  $r->update(['payment_status'=>'refunded','refunded_cents'=>1000]);$this->assertSame(800,$c->fresh()->funding()['allocation_shortfall_cents']);
 }
 public function test_payment_admin_screens_render_but_inactive_admin_is_denied(): void {
  $c=$this->campaign();$r=$this->registration($c,paid:true);$this->closeVote($c);app(RegistrationDecision::class)->record($r,'not_selected','Signed local vote result');
  \Livewire\Livewire::test(\App\Filament\Resources\RegistrationCampaignResource\Pages\ManageRecords::class)->mountAction(\Filament\Actions\Testing\TestAction::make('edit')->table($c))->assertHasNoActionErrors();
  foreach(['RegistrationCampaign','CandidateRegistration','DrinkCredit'] as $name){$class='App\\Filament\\Resources\\'.$name.'Resource';$this->get($class::getUrl())->assertOk();}
  $admin=auth('admin')->user();$admin->update(['is_active'=>false]);$this->get(\App\Filament\Resources\DrinkCreditResource::getUrl())->assertForbidden();
 }
 public function test_selected_candidate_gets_no_credit_and_test_money_cannot_fund_live_event(): void {
  $c=$this->campaign();$r=$this->registration($c,paid:true);$team=$c->eventProject->teams()->create(['name'=>'Team','freguesia'=>'Belmonte']);
  foreach($team->roleAssignments as $role)$role->update(['interest_id'=>$r->interest_id,'candidate_name'=>'Ana','candidate_reference'=>'register-ana','consent_confirmed'=>true,'competence_evidence'=>'Local qualifications checked','status'=>'confirmed']);
  $team->update(['role_cumulation_evidence'=>'Timetables, local vote and responsibilities reviewed','election_minutes'=>'Signed ballot minutes']);$team->update(['status'=>'elected']);$this->closeVote($c);app(RegistrationDecision::class)->record($r,'selected','Selected in signed local vote');
  $this->assertDatabaseCount('drink_credits',0);$this->assertSame(0,$c->funding()['potential_drink_credit_cents']);config(['registration.live'=>true]);$this->assertFalse($r->fresh()->isPaid());$this->assertSame(0,$c->funding()['gross_cents']);$this->assertFalse($team->fresh()->composition()['complete']);
 }

 public function test_signed_completion_recovers_a_checkout_created_before_a_local_transaction_failed(): void {
  $c=$this->campaign();$r=$this->registration($c);$remote=$this->stripeSessionFixture($r);$remote['metadata']['attempt']='1';Http::fake(['api.stripe.com/*'=>Http::response($remote)]);
  $this->webhook(['id'=>'evt_recovered','type'=>'checkout.session.completed','livemode'=>false,'data'=>['object'=>['id'=>'cs_test_example','metadata'=>['registration'=>$r->public_id]]]])->assertOk();
  $this->assertTrue($r->fresh()->isPaid());$this->assertSame('cs_test_example',$r->fresh()->stripe_session_id);
 }

}
