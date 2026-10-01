<?php
namespace Tests\Feature;
use App\Models\{Admin,EventProject,FinancialPlan,FinancialProfile,Scenario,CommercialPlan};
use App\Domain\Finance\{FinancialProjection,FinancialSources,CommercialPricing};
use App\Filament\Pages\FinancialPlanning;
use App\Livewire\FinancialOverview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Filament\Actions\Testing\TestAction;
use Tests\TestCase;
class FinancialPlanningTest extends TestCase {
 use RefreshDatabase;
 private function plan(): FinancialPlan {
  $p=EventProject::create(['name'=>'Financial test','slug'=>'financial-'.EventProject::count(),'starts_at'=>'2026-12-12 10:00:00']);
  $s=$p->scenarios()->create(['name'=>'Financial scenario','organizer_net_monthly_cents'=>0,'organizer_full_monthly_cents'=>0,'target_surplus_cents'=>0,'contingency_cents'=>0,'refund_reserve_cents'=>0,'costs_complete'=>true,'event_days'=>2]);
  return FinancialPlan::create(['scenario_id'=>$s->id,'vat_regime'=>'normal','vat_evidence'=>'Test normal monthly regime','phases'=>[
   ['key'=>'pre','label'=>'Préparer','date'=>'2026-10-01','vat_payment_phase'=>'live'],['key'=>'live','label'=>'Événement','date'=>'2026-12-12','vat_payment_phase'=>'close'],['key'=>'close','label'=>'Clôturer','date'=>'2027-02-25','vat_payment_phase'=>'close'],
  ]]);
 }
 private function line(FinancialPlan $p,string $kind,int $amount,array $metadata=[],array $source=[]): FinancialProfile {
  $line=$p->scenario->budgetLines()->create(array_replace(['name'=>'Poste '.\App\Models\BudgetLine::count(),'kind'=>$kind,'forecast_quantity'=>1,'unit_gross_cents'=>$amount,'vat_basis_points'=>2300],$source));
  $row=collect(app(FinancialSources::class)->rows($p->fresh()))->firstWhere('key','budget:'.$line->id);
  return $p->profiles()->create(array_replace(app(FinancialSources::class)->defaults($row,$p),['source_type'=>'budget','source_id'=>$line->id,'invoice_phase'=>'pre','consumption_phase'=>'live','cash_schedule'=>[['phase'=>'pre','share_basis_points'=>10000]],'vat_schedule'=>[['phase'=>'pre','share_basis_points'=>10000]],'vat_treatment'=>'domestic','vat_evidence'=>'Invoice classification','deduction_basis_points'=>$kind==='cost'?10000:0,'schedule_evidence'=>'Payment and delivery terms'],$metadata));
 }
 private function admin(): Admin {$a=Admin::create(['name'=>'Admin','email'=>'financial@example.test','password'=>'long-financial-password']);$this->actingAs($a,'admin');return $a;}
 private function invalid(callable $f): void {try{$f();$this->fail('Validation expected');}catch(ValidationException){$this->assertTrue(true);}}
 public function test_tax_cash_profit_and_break_even_are_different_and_integer_rounded(): void {
  $p=$this->plan();$this->line($p,'revenue',12300,['behavior'=>'variable'],['forecast_quantity'=>10]);$this->line($p,'cost',12300);$this->line($p,'cost',6150,['behavior'=>'variable'],['forecast_quantity'=>10]);
  $r=$p->fresh()->report();$this->assertSame(40000,$r['result_cents']);$this->assertSame(20000,$r['break_even_sales_cents']);$this->assertEquals(20,$r['break_even_volume_pct']);$this->assertSame(23000,$r['vat_output_cents']);$this->assertSame(13800,$r['vat_input_cents']);$this->assertSame(9200,$r['vat_paid_cents']);$this->assertSame(40000,$r['closing_cash_cents']);$this->assertSame(40000,$r['phases'][0]['free_cash']);
  $r=$p->fresh()->report(['price'=>50]);$this->assertNull($r['break_even_sales_cents']);$this->assertSame(-10000,$r['result_cents']);
  $a=app(FinancialProjection::class)->allocate(2,[['phase'=>'a','share_basis_points'=>2600],['phase'=>'b','share_basis_points'=>2600],['phase'=>'c','share_basis_points'=>2600],['phase'=>'d','share_basis_points'=>2200]],['a','b','c','d'],'a');$this->assertSame(2,array_sum($a));$this->assertGreaterThanOrEqual(0,min($a));
 }
 public function test_capex_charge_loan_and_restricted_cash_do_not_create_fake_profit(): void {
  $p=$this->plan();$this->line($p,'cost',123000,['depreciation_unit_cents'=>10000],['expense_type'=>'investment']);
  $p->update(['financing'=>[['name'=>'Loan','kind'=>'loan','amount_cents'=>123000,'phase'=>'pre'],['name'=>'Deposit','kind'=>'restricted_in','amount_cents'=>50000,'phase'=>'pre']]]);
  $r=$p->fresh()->report();$this->assertSame(-10000,$r['result_cents']);$this->assertSame(123000,$r['investment_cash_cents']);$this->assertSame(50000,$r['closing_cash_cents']);$this->assertSame(0,$r['phases'][0]['free_cash']);$this->assertSame(0,$r['peak_bfr_cents']);$this->assertSame(23000,$r['vat_credit_cents']);$this->assertSame(0,$r['vat_paid_cents']);
 }
 public function test_bfr_tracks_stock_receivables_payables_and_advances(): void {
  $p=$this->plan();$this->line($p,'cost',12300,['is_stock'=>true,'cash_schedule'=>[['phase'=>'close','share_basis_points'=>10000]]]);
  $this->line($p,'revenue',24600,['cash_schedule'=>[['phase'=>'close','share_basis_points'=>10000]]]);
  $this->line($p,'revenue',12300,['invoice_phase'=>'live']);
  $r=$p->fresh()->report();$pre=$r['phases'][0];$this->assertSame(10000,$pre['stock']);$this->assertSame(24600,$pre['receivables']);$this->assertSame(12300,$pre['payables']);$this->assertSame(12300,$pre['customer_advances']);$this->assertSame(10000,$pre['bfr']);$this->assertSame(12300,$r['phases'][1]['bfr']);$this->assertSame(0,$r['phases'][2]['bfr']);
 }
 public function test_partial_deduction_foreign_vat_reverse_charge_and_source_changes(): void {
  $p=$this->plan();$cost=$this->line($p,'cost',12300,['deduction_basis_points'=>5000]);$this->line($p,'cost',11900,['vat_treatment'=>'foreign','deduction_basis_points'=>0],['vat_basis_points'=>1900]);$this->line($p,'cost',10000,['vat_treatment'=>'reverse','deduction_basis_points'=>5000]);
  $r=$p->fresh()->report();$this->assertSame(2300,$r['vat_input_cents']);$this->assertSame(2300,$r['vat_output_cents']);$this->assertSame(-34200,$r['result_cents']);
  $cost->source()->update(['vat_basis_points'=>1300]);$r=$p->fresh()->report();$this->assertSame(1150,$r['vat_input_cents']);$this->assertStringContainsString('nature ou taux modifié',implode(' ',$r['issues']));
  $p->update(['vat_regime'=>'exempt']);$r=$p->fresh()->report();$this->assertSame(0,$r['vat_input_cents']);
 }
 public function test_vat_on_advances_cannot_be_deferred_to_event_and_phase_references_are_guarded(): void {
  $p=$this->plan();$revenue=$this->line($p,'revenue',12300,['invoice_phase'=>'live','cash_schedule'=>[['phase'=>'pre','share_basis_points'=>5000],['phase'=>'close','share_basis_points'=>5000]],'vat_schedule'=>[['phase'=>'pre','share_basis_points'=>5000],['phase'=>'live','share_basis_points'=>5000]]]);
  $this->assertSame(1150,$p->fresh()->report()['phases'][0]['vat_output']);
  $this->invalid(fn()=>$revenue->update(['vat_schedule'=>[['phase'=>'live','share_basis_points'=>10000]]]));
  $this->invalid(fn()=>$revenue->fresh()->update(['cash_schedule'=>[['phase'=>'pre','share_basis_points'=>5000]]]));
  $phases=$p->phases;$phases[0]['key']='different';$this->invalid(fn()=>$p->update(['phases'=>$phases]));
 }
 public function test_source_quantities_and_global_drivers_update_without_double_counting_materials(): void {
  $p=$this->plan();$s=$p->scenario;$driver=$this->line($p,'cost',1230,['driver'=>'stands','behavior'=>'variable']);
  $stand=$s->eventProject->stands()->create(['name'=>'First','kind'=>'village']);$s->includedStands()->attach($stand);$this->assertSame(1000,$p->fresh()->report()['variable_cost_cents']);
  $other=$s->eventProject->stands()->create(['name'=>'Second','kind'=>'independent']);$s->includedStands()->attach($other);$this->assertSame(2000,$p->fresh()->report()['variable_cost_cents']);
  $common=$this->line($p,'cost',12300,[],['costing_key'=>'broadcast']);
  $a=$s->eventProject->activities()->create(['name'=>'Game','rules'=>'Play','planned_runs'=>3,'materials_complete'=>true]);$s->includedActivities()->attach($a);
  $a->materials()->create(['name'=>'Shared','quantity'=>1,'unit_gross_cents'=>12300,'vat_basis_points'=>2300,'shared_cost_key'=>'broadcast']);$a->materials()->create(['name'=>'Per run','quantity'=>2,'basis'=>'per_run','unit_gross_cents'=>1000,'vat_basis_points'=>0]);
  $r=$p->fresh()->report();$this->assertCount(3,$r['details']);$this->assertSame(6,collect($r['details'])->first(fn($d)=>str_contains($d['name'],'Per run'))['quantity']);
  $this->invalid(fn()=>$driver->update(['source_id'=>$common->source_id]));
 }
 public function test_unknown_prices_tax_and_scope_never_produce_a_complete_plan(): void {
  $p=$this->plan();$this->line($p,'cost',10000,[],['vat_basis_points'=>null]);$unknown=$this->line($p,'cost',10000);$unknown->source()->update(['unit_gross_cents'=>null]);
  $r=$p->fresh()->report();$this->assertFalse($r['complete']);$this->assertSame(-10000,$r['result_cents']);$this->assertStringContainsString('à chiffrer',implode(' ',$r['issues']));
 }
 public function test_simulation_and_central_editor_keep_signed_prices_and_require_active_admin(): void {
  $p=$this->plan();$profile=$this->line($p,'revenue',12300,['behavior'=>'variable'],['committed_quantity'=>1]);$r=$p->fresh()->report(['price'=>200,'volume'=>200]);$this->assertSame(10000,$r['revenue_cents']);$this->assertSame(12300,$profile->source()->unit_gross_cents);
  $this->get(FinancialPlanning::getUrl())->assertRedirect();Livewire::test(FinancialOverview::class,['scenarioId'=>$p->scenario->public_id])->assertForbidden();
  $admin=$this->admin();$this->invalid(fn()=>app(FinancialSources::class)->edit($profile,['source_unit_cents'=>24600,'source_quantity'=>1,'source_vat_rate'=>2300]));
  $this->get(FinancialPlanning::getUrl(['scenario'=>$p->scenario->public_id]))->assertOk()->assertSee('Rentabilité et trésorerie');
  Livewire::test(FinancialOverview::class,['scenarioId'=>$p->scenario->public_id])->set('price',200)->assertSee('Simulation temporaire')->call('resetSimulation')->assertSet('price',100);
  $admin->update(['is_active'=>false]);Livewire::test(FinancialOverview::class,['scenarioId'=>$p->scenario->public_id])->assertForbidden();
 }
 public function test_filament_parameters_and_cost_form_persist_real_sources(): void {
  $p=$this->plan();$profile=$this->line($p,'cost',12300);$this->admin();
  $page=Livewire::test(FinancialPlanning::class)->assertCanSeeTableRecords([$profile]);
  $page->callAction('parameters',data:['opening_cash_cents'=>30000,'scenario_organizer_full_monthly_cents'=>80000,'scenario_organizer_cost_evidence'=>'Coût complet pour la période'])->assertHasNoActionErrors();$this->assertSame(30000,$p->fresh()->opening_cash_cents);$this->assertSame(80000,$p->fresh()->scenario->organizer_full_monthly_cents);
  $page->callAction(TestAction::make('editProfile')->table($profile),data:['source_unit_cents'=>24600,'source_price_evidence'=>'Revised quote'])->assertHasNoActionErrors();$this->assertSame(24600,$profile->source()->unit_gross_cents);$this->assertSame('estimate',$profile->source()->pricing_status);
 }
 public function test_policy_upgrades_only_unpublished_unagreed_initial_prices_and_is_idempotent(): void {
  $this->seed();$p=EventProject::where('slug','os-jogos-do-agricultor')->sole();$plan=CommercialPlan::where('scenario_id',$p->launchScenario()->id)->sole();$lines=app(CommercialPricing::class)->lines($plan)->filter(fn($l)=>$l->stand->kind==='village')->values();
  foreach($lines as $line){$this->assertSame(100000,$line->unit_gross_cents);$this->assertSame(100000,$line->stand->sponsorship_total_cents);}
  $this->assertSame(100000,$plan->village_price_cents);$this->assertDatabaseCount('financial_plans',1);
  $p->update(['financial_policy_version'=>null]);$plan->update(['village_price_cents'=>50000]);
  $lines[0]->update(['unit_gross_cents'=>50000,'committed_quantity'=>1]);$lines[1]->update(['unit_gross_cents'=>70000]);
  $this->seed(\Database\Seeders\FinancialPlanningSeeder::class);$this->assertSame(50000,$lines[0]->fresh()->unit_gross_cents);$this->assertSame(70000,$lines[1]->fresh()->unit_gross_cents);
  $this->seed(\Database\Seeders\FinancialPlanningSeeder::class);$this->assertDatabaseCount('financial_plans',1);$this->assertSame(0,(int)$p->launchScenario()->budgetLines()->sum('paid_quantity'));
 }
}
