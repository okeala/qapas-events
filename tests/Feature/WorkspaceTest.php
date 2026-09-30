<?php
namespace Tests\Feature;
use App\Models\{Admin,EventProject,Interest,Scenario,User};
use App\Domain\Planning\{Readiness,PhaseTransition};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
class WorkspaceTest extends TestCase {
 use RefreshDatabase;
 private function project(bool $public=true): EventProject {return EventProject::create(['name'=>'Test','slug'=>'test','is_public'=>$public]);}
 public function test_seed_is_idempotent_and_makes_no_financial_promise(): void {
  $this->seed();$this->seed();$this->assertDatabaseCount('event_projects',1);$this->assertDatabaseCount('legal_requirements',9);
  foreach(Scenario::all() as $s){$this->assertFalse($s->report()['complete']);$this->assertFalse($s->report()['target_secured']);}
 }
 public function test_public_pages_render_both_locales_without_platform(): void {
  $this->seed();$this->get('/')->assertOk()->assertSee('Forqua de Ouro');
  $this->get('/events/forqua-de-ouro')->assertOk()->assertSee('500,00');
  $this->withSession(['locale'=>'pt'])->get('/')->assertOk()->assertSee('Fazer crescer');
  $this->get('/privacy')->assertOk();
 }
 public function test_private_edition_is_not_readable_or_submittable(): void {
  $this->project(false);$this->get('/events/test')->assertNotFound();$this->post('/events/test/interest',[])->assertNotFound();
 }
 public function test_interest_requires_privacy_readiness_and_keeps_marketing_separate(): void {
  $p=$this->project();$data=['name'=>'Ana','email'=>'ana@example.test','profile'=>'resident','privacy'=>1,'status'=>'paid','event_project_id'=>999];
  $this->post('/events/test/interest',$data)->assertStatus(503);
  config(['events.privacy_ready'=>true,'events.organizer_name'=>'Test organization','events.contact_email'=>'contact@example.test']);
  $this->post('/events/test/interest',$data)->assertRedirect('/events/test');
  $lead=Interest::firstOrFail();$this->assertFalse($lead->marketing_opt_in);$this->assertSame('new',$lead->status);$this->assertSame($p->id,$lead->event_project_id);
 }
 public function test_interest_honeypot_and_missing_ack_are_rejected(): void {
  $this->project();config(['events.privacy_ready'=>true,'events.organizer_name'=>'Test','events.contact_email'=>'contact@example.test']);
  $this->post('/events/test/interest',['name'=>'Ana','email'=>'ana@example.test','profile'=>'resident','website'=>'spam'])->assertSessionHasErrors(['privacy','website']);$this->assertDatabaseCount('interests',0);
 }
 public function test_default_and_frontend_users_cannot_enter_admin(): void {
  $this->get('/admin')->assertRedirect('/admin/login');
  $user=new User(['name'=>'Person','email'=>'person@example.test','password'=>'a-long-password-for-tests']);$user->public_id=(string)\Illuminate\Support\Str::uuid();$user->save();
  $this->actingAs($user,'web')->get('/admin')->assertRedirect('/admin/login');
 }
 public function test_active_admin_can_render_every_resource_and_no_other_guard_is_used(): void {
  $this->seed();$admin=Admin::create(['name'=>'Admin','email'=>'admin@example.test','password'=>'a-long-password-for-tests']);
  $this->actingAs($admin,'admin')->get('/admin')->assertOk();
  foreach(['EventProject','Scenario','BudgetLine','Offer','Interest','Idea','LegalRequirement','Team','Activity','RunItem','Incident','Stand','Debrief'] as $model){
   $class='App\\Filament\\Resources\\'.$model.'Resource';$this->get($class::getUrl())->assertOk();
  }
  $admin->update(['is_active'=>false]);$this->get('/admin')->assertForbidden();
 }
 public function test_real_form_can_create_an_edition_with_requirements(): void {
  $admin=Admin::create(['name'=>'Admin','email'=>'admin@example.test','password'=>'a-long-password-for-tests']);$this->actingAs($admin,'admin');
  Livewire::test(\App\Filament\Resources\EventProjectResource\Pages\ManageRecords::class)->callAction('create',data:['name'=>'New event','slug'=>'new-event','is_public'=>false,'capacity'=>0])->assertHasNoActionErrors();
  $this->assertDatabaseHas('event_projects',['slug'=>'new-event']);$this->assertDatabaseCount('legal_requirements',9);
 }
 public function test_gate_cannot_be_bypassed_by_deleting_a_requirement_or_inventing_review(): void {
  $p=$this->project();$p->requirements()->delete();$this->assertNotEmpty(app(Readiness::class)->blockers($p,'sales'));
  $p->update(['phase'=>'preparation']);$this->expectException(\Illuminate\Validation\ValidationException::class);app(PhaseTransition::class)->advance($p);
 }
 public function test_costs_are_reserved_even_without_signed_supplier_and_restricted_money_never_counts_as_margin(): void {
  $p=$this->project();$s=$p->scenarios()->create(['name'=>'Test','months'=>1,'organizer_full_monthly_cents'=>160000,'costs_complete'=>true]);
  $s->budgetLines()->create(['name'=>'Sales','kind'=>'revenue','unit_gross_cents'=>123000,'vat_basis_points'=>2300,'forecast_quantity'=>10,'committed_quantity'=>5,'paid_quantity'=>2]);
  $s->budgetLines()->create(['name'=>'Site','kind'=>'cost','unit_gross_cents'=>123000,'vat_basis_points'=>2300,'deductible'=>true,'forecast_quantity'=>1,'committed_quantity'=>0,'paid_quantity'=>0]);
  $s->budgetLines()->create(['name'=>'Deposits','kind'=>'deposit','unit_gross_cents'=>1000000,'forecast_quantity'=>1,'committed_quantity'=>1,'paid_quantity'=>1]);
  $r=$s->report();$this->assertSame(740000,$r['forecast_margin_cents']);$this->assertSame(240000,$r['secured_margin_cents']);$this->assertSame(160000,$r['target_gap_cents']);$this->assertSame(1000000,$r['restricted_receipts_cents']);$this->assertFalse($r['target_secured']);
 }
 public function test_unknown_tax_rate_does_not_become_zero(): void {
  $p=$this->project();$s=$p->scenarios()->create(['name'=>'Test','organizer_full_monthly_cents'=>160000,'costs_complete'=>true]);
  $s->budgetLines()->create(['name'=>'Cost','kind'=>'cost','unit_gross_cents'=>100,'forecast_quantity'=>1]);
  $this->assertFalse($s->report()['complete']);
 }
 public function test_budget_form_rejects_paid_quantity_above_commitment(): void {
  $admin=Admin::create(['name'=>'Admin','email'=>'admin@example.test','password'=>'a-long-password-for-tests']);$this->actingAs($admin,'admin');
  $p=$this->project();$s=$p->scenarios()->create(['name'=>'Small']);
  Livewire::test(\App\Filament\Resources\BudgetLineResource\Pages\ManageRecords::class)
   ->callAction('create',data:['scenario_id'=>$s->id,'name'=>'Stand','kind'=>'revenue','unit_gross_cents'=>50000,'vat_basis_points'=>2300,'deductible'=>false,'forecast_quantity'=>5,'committed_quantity'=>1,'paid_quantity'=>2])
   ->assertHasActionErrors(['paid_quantity']);
  $this->assertDatabaseCount('budget_lines',0);
 }
 public function test_readiness_accepts_complete_evidence_but_rejects_expiry_before_event_end(): void {
  $p=$this->project();$p->update(['phase'=>'preparation','venue'=>'Agreed site','capacity'=>300,'starts_at'=>now()->addDays(30),'ends_at'=>now()->addDays(31)]);
  foreach($p->requirements as $item) $item->update(['status'=>'approved','evidence'=>'Reviewed source reference','reviewed_by'=>'Responsible reviewer','reviewed_at'=>now(),'expires_at'=>now()->addDays(40)]);
  $s=$p->scenarios()->create(['name'=>'Viable','months'=>1,'organizer_full_monthly_cents'=>160000,'costs_complete'=>true]);
  $s->budgetLines()->create(['name'=>'Revenue','kind'=>'revenue','unit_gross_cents'=>123000,'vat_basis_points'=>2300,'forecast_quantity'=>10,'committed_quantity'=>10]);
  $s->budgetLines()->create(['name'=>'Costs','kind'=>'cost','unit_gross_cents'=>123000,'vat_basis_points'=>2300,'deductible'=>true,'forecast_quantity'=>1]);
  $p->runItems()->create(['name'=>'Opening','owner'=>'Responsible','starts_at'=>$p->starts_at,'ends_at'=>$p->ends_at]);
  foreach(['official','public'] as $track) $p->activities()->create(['name'=>$track,'track'=>$track,'rules'=>'Tested rules','referee'=>'Independent referee','capacity'=>6,'risk_reviewed'=>true,'risk_evidence'=>'Validated risk review','status'=>'approved']);
  $this->assertSame([],app(Readiness::class)->blockers($p,'live'));
  app(PhaseTransition::class)->advance($p);$this->assertSame('ready',$p->fresh()->phase);
  $p->requirements()->where('code','insurance')->update(['expires_at'=>now()->addDays(1)]);
  $this->assertNotEmpty(app(Readiness::class)->blockers($p,'live'));
  $this->assertNotEmpty(app(Readiness::class)->blockers($p,'sales'));
 }

}
