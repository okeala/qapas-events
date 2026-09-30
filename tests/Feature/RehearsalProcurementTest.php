<?php
namespace Tests\Feature;
use App\Models\{Admin,ActivityTrial,BudgetLine,CupPlan,CostConsultation,EventProject,Scenario,SupplierQuote};
use App\Domain\Procurement\Consultations;
use App\Domain\Planning\Readiness;
use App\Domain\Finance\BudgetIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class RehearsalProcurementTest extends TestCase {
 use RefreshDatabase;
 private function admin(): void {$this->actingAs(Admin::firstOrCreate(['email'=>'preparation@example.test'],['name'=>'Admin','password'=>'long-test-password']),'admin');}
 private function project(): EventProject {return EventProject::create(['name'=>'Preparation','slug'=>'preparation','is_public'=>true,'require_activity_trials'=>true,'starts_at'=>now()->addDays(3),'ends_at'=>now()->addDays(4)]);}
 private function trial($a,string $stage,int $days=1): ActivityTrial {return $a->trials()->create(['stage'=>$stage,'performed_at'=>now()->subDays($days),'result'=>'passed','responsible'=>'Responsible','location'=>'Verified location','volunteers'=>6,'evidence'=>'Performed safely, timings, materials and observations documented.']);}
 private function invalid(callable $run,string $key): void {try{$run();$this->fail('Expected validation error '.$key);}catch(ValidationException $e){$this->assertArrayHasKey($key,$e->errors());}}
 public function test_seed_is_idempotent_keeps_individual_prices_and_prepares_all_cost_letters(): void {
  $this->seed();$s=Scenario::where('template_key','costing-rental-experts-v1')->firstOrFail();$this->assertTrue($s->eventProject->require_activity_trials);$this->assertDatabaseCount('cup_plans',1);$this->assertDatabaseCount('activity_trials',6);$plan=CupPlan::first();
  $this->assertSame(500,(int)$plan->allocations()->whereHas('stand',fn($q)=>$q->where('kind','village'))->sum('planned_quantity'));$this->assertSame(500,(int)$plan->allocations()->whereHas('stand',fn($q)=>$q->where('kind','independent'))->sum('planned_quantity'));$this->assertSame(0,(int)$plan->report()['issued']);$this->assertNull($plan->report()['stock_margin_cents']);$this->assertSame('unverified',$plan->hot_food_status);
  $transport=$s->budgetLines()->where('name','Mini-rétro : transport aller et reprise')->first();$this->assertSame(15000,$transport->unit_gross_cents);$this->assertSame(1,$transport->forecast_quantity);$this->assertNull($transport->vat_basis_points);$transport->update(['unit_gross_cents'=>17000]);
  $c=app(Consultations::class)->ensure($transport);$c->update(['body_pt'=>'My edited draft']);$count=CostConsultation::count();$this->seed();$this->assertSame($count,CostConsultation::count());$this->assertSame('My edited draft',$c->fresh()->body_pt);$this->assertSame(17000,$transport->fresh()->unit_gross_cents);$this->assertDatabaseCount('supplier_quotes',0);
  foreach($s->budgetLines()->where('kind','cost')->get() as $line)$this->assertNotNull(CostConsultation::where('costable_type',BudgetLine::class)->where('costable_id',$line->id)->first());
  $this->get('/events/os-jogos-do-agricultor')->assertOk()->assertSee('son stand reste');$this->withSession(['locale'=>'pt'])->get('/events/os-jogos-do-agricultor')->assertOk()->assertSee('a banca mantém-se');
 }
 public function test_official_rehearsal_is_required_and_rule_changes_invalidate_it(): void {
  $this->admin();$p=$this->project();$a=$p->activities()->create(['name'=>'Official','rules'=>'Rules','track'=>'official']);$this->assertFalse($a->preparation()->ready($a));$this->trial($a,'official_rehearsal');$this->assertTrue($a->fresh()->preparation()->ready($a->fresh()));$a->update(['rules'=>'Changed rules']);$this->assertFalse($a->fresh()->preparation()->ready($a->fresh()));
 }
 public function test_external_challenge_requires_local_then_installation_in_final_week_and_stand_survives(): void {
  $this->admin();$p=$this->project();$stand=$p->stands()->create(['name'=>'Village','kind'=>'village']);$a=$p->activities()->create(['name'=>'External','rules'=>'Rules','proposer_type'=>'village','stand_id'=>$stand->id,'is_public'=>true]);
  $this->invalid(fn()=>$this->trial($a,'site_installation'),'result');$this->trial($a,'local_test',3);$this->invalid(fn()=>$this->trial($a,'site_installation',10),'result');$this->trial($a,'site_installation');$this->assertTrue($a->fresh()->preparation()->ready($a->fresh()));
  $a->preparation()->withdraw($a,'Not ready after final review');$this->assertFalse($a->fresh()->publicVisible());$this->assertDatabaseHas('stands',['id'=>$stand->id,'name'=>'Village']);$this->assertSame('stand_only',$a->fresh()->participation_decision);
 }
 public function test_unready_external_challenge_does_not_add_live_safety_blocker_but_keeps_its_cost(): void {
  $p=$this->project();$a=$p->activities()->create(['name'=>'Optional','rules'=>'Rules','proposer_type'=>'independent','status'=>'approved','risk_category'=>'machinery']);$s=$p->scenarios()->create(['name'=>'Scenario']);$s->includedActivities()->attach($a);$a->materials()->create(['name'=>'Already planned','quantity'=>1,'unit_gross_cents'=>12300,'vat_basis_points'=>0]);
  $blockers=app(Readiness::class)->blockers($p,'live');$this->assertNotContains('Activité validée sans sécurité, capacité ou responsable',$blockers);$this->assertFalse(collect($blockers)->contains(fn($b)=>str_starts_with($b,'Optional :')));$this->assertSame(12300,$s->report()['activity_cost_cents']);
 }
 public function test_passed_trial_requires_admin_and_cannot_be_future(): void {
  $p=$this->project();$a=$p->activities()->create(['name'=>'Official','rules'=>'Rules']);$this->invalid(fn()=>$this->trial($a,'official_rehearsal'),'result');$this->admin();$this->invalid(fn()=>$this->trial($a,'official_rehearsal',-1),'result');
 }
 public function test_multiple_offers_and_private_drafts_do_not_create_costs_or_payments(): void {
  $p=$this->project();$s=$p->scenarios()->create(['name'=>'Scenario']);$line=$s->budgetLines()->create(['name'=>'Transport','kind'=>'cost','unit'=>'forfait','forecast_quantity'=>1]);$c=app(Consultations::class)->ensure($line);
  $url=route('consultation.email',['consultation'=>$c,'locale'=>'pt']);$this->get($url)->assertForbidden();$this->admin();$this->get($url)->assertOk()->assertHeader('Content-Type','message/rfc822')->assertSee('X-Unsent: 1');
  $data=['supplier'=>'Local supplier','reference'=>'Offer 1','received_at'=>today(),'valid_until'=>today(),'quantity'=>1,'unit'=>'forfait','unit_gross_cents'=>15000,'vat_basis_points'=>2300,'delivery_cents'=>0,'other_cents'=>0,'deposit_cents'=>0,'document_reference'=>'Quote PDF stored at supplier folder'];$quote=$c->quotes()->create($data);$c->quotes()->create(array_replace($data,['supplier'=>'Village owner','unit_gross_cents'=>5000,'source_type'=>'local_loan']));
  $this->assertNull($line->fresh()->unit_gross_cents);$this->assertDatabaseCount('budget_lines',1);app(Consultations::class)->apply($quote);app(Consultations::class)->apply($quote);$this->assertSame(15000,$line->fresh()->unit_gross_cents);$this->assertSame('quoted',$line->fresh()->pricing_status);$this->assertSame(0,$line->fresh()->paid_quantity);$this->assertSame(0,$line->fresh()->committed_quantity);
  $this->invalid(fn()=>$quote->fresh()->update(['unit_gross_cents'=>16000]),'supplier');auth('admin')->user()->update(['is_active'=>false]);$this->get($url)->assertForbidden();
 }
 public function test_quotes_cannot_drop_fees_deposit_or_quantity_mismatch(): void {
  $this->admin();$p=$this->project();$s=$p->scenarios()->create(['name'=>'Scenario']);$line=$s->budgetLines()->create(['name'=>'Machine','kind'=>'cost','unit'=>'jour','forecast_quantity'=>2]);$c=app(Consultations::class)->ensure($line);
  $data=['supplier'=>'Rental','received_at'=>today(),'quantity'=>2,'unit'=>'jour','unit_gross_cents'=>1000,'vat_basis_points'=>2300,'delivery_cents'=>0,'other_cents'=>0,'deposit_cents'=>0,'document_reference'=>'Document'];
  foreach([['delivery_cents'=>500],['deposit_cents'=>500],['quantity'=>1],['vat_basis_points'=>null]] as $change){$q=$c->quotes()->create(array_replace($data,$change));$this->invalid(fn()=>app(Consultations::class)->apply($q),'quote');}$this->assertNull($line->fresh()->unit_gross_cents);
 }
 public function test_shared_broadcast_has_one_consultation_and_retains_edited_letter(): void {
  $this->seed();$s=Scenario::where('template_key','costing-rental-experts-v1')->first();$materials=$s->includedActivities->flatMap->materials->filter(fn($m)=>filled($m->shared_cost_key));$this->assertNotEmpty($materials);$ids=$materials->map(fn($m)=>app(Consultations::class)->ensure($m)->id);$this->assertSame(1,$ids->unique()->count());
 }
 public function test_same_post_cannot_be_duplicated_within_unit_but_can_exist_in_another_scenario(): void {
  $p=$this->project();$s=$p->scenarios()->create(['name'=>'Scenario']);$l=$s->budgetLines()->create(['name'=>'Électricité - site','kind'=>'cost','unit_gross_cents'=>5000,'forecast_quantity'=>1]);$this->invalid(fn()=>$s->budgetLines()->create(['name'=>' electricite SITE ','kind'=>'cost']),'name');$other=$p->scenarios()->create(['name'=>'Other']);$other->budgetLines()->create(['name'=>'Électricité - site','kind'=>'cost']);$this->assertDatabaseCount('budget_lines',2);
  $row=$l->getAttributes();unset($row['id']);$row['public_id']=(string)Str::uuid();$row['identity_key']=null;$id=DB::table('budget_lines')->insertGetId($row);$duplicate=BudgetLine::find($id);$this->assertContains('Doublons budgétaires à examiner dans ce scénario',$s->fresh()->report()['missing']);$this->admin();app(BudgetIdentity::class)->supersede($duplicate,$l->id);$this->assertSame(1,$s->budgetLines()->count());$this->assertSame($l->id,$duplicate->fresh()->superseded_by_id);$this->assertDatabaseCount('budget_lines',3);
 }
 public function test_stock_allocations_require_received_stock_and_proof_and_pool_limits(): void {
  $this->seed();$plan=CupPlan::first();$a=$plan->allocations()->first();$this->invalid(fn()=>$a->update(['issued_quantity'=>1]),'issued_quantity');$plan->update(['received_quantity'=>1000,'receipt_evidence'=>'Signed delivery']);$this->invalid(fn()=>$a->fresh()->update(['issued_quantity'=>1]),'handover_evidence');$a->fresh()->update(['issued_quantity'=>10,'responsible'=>'Stand keeper','handover_evidence'=>'Signed handover']);$this->assertSame(990,$plan->fresh()->report()['available']);$this->invalid(fn()=>$a->fresh()->update(['planned_quantity'=>100]),'planned_quantity');$this->invalid(fn()=>$plan->fresh()->update(['hot_food_status'=>'confirmed']),'hot_food_evidence');$this->assertNull($plan->fresh()->report()['stock_margin_cents']);$this->assertSame(0,(int)BudgetLine::sum('paid_quantity'));
 }
 public function test_new_admin_forms_grouped_budgets_and_relations_render(): void {
  $this->seed();$this->admin();foreach(['ActivityTrial','CostConsultation','CupPlan','BudgetLine'] as $name){$class='App\\Filament\\Resources\\'.$name.'Resource';$this->get($class::getUrl())->assertOk();}
  $c=CostConsultation::first();$plan=CupPlan::first();$this->get(\App\Filament\Resources\CostConsultationResource::getUrl('edit',['record'=>$c]))->assertOk();$this->get(\App\Filament\Resources\CupPlanResource::getUrl('edit',['record'=>$plan]))->assertOk();
  \Livewire\Livewire::test(\App\Filament\Resources\ActivityTrialResource\Pages\ManageRecords::class)->mountAction(\Filament\Actions\Testing\TestAction::make('edit')->table(ActivityTrial::first()))->assertHasNoActionErrors();
  foreach([[\App\Filament\RelationManagers\SupplierQuotesRelationManager::class,$c,\App\Filament\Resources\CostConsultationResource\Pages\EditRecord::class],[\App\Filament\RelationManagers\CupAllocationsRelationManager::class,$plan,\App\Filament\Resources\CupPlanResource\Pages\EditRecord::class]] as [$class,$record,$page])\Livewire\Livewire::test($class,['ownerRecord'=>$record,'pageClass'=>$page])->mountAction(\Filament\Actions\Testing\TestAction::make('create')->table())->assertHasNoActionErrors();
 }
}
