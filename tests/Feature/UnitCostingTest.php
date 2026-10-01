<?php
namespace Tests\Feature;
use App\Models\{Admin,EventProject,Scenario,Activity};
use App\Domain\Finance\{UnitCosting,Pricing};
use App\Filament\RelationManagers\{CostsRelationManager,RevenuesRelationManager,MaterialsRelationManager};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Filament\Actions\Testing\TestAction;
use Tests\TestCase;
class UnitCostingTest extends TestCase {
 use RefreshDatabase;
 private function admin(): Admin {$a=Admin::create(['name'=>'Admin','email'=>'costing@example.test','password'=>'long-testing-password']);$this->actingAs($a,'admin');return $a;}
 private function scenario(): Scenario {$p=EventProject::create(['name'=>'Test','slug'=>'costing-test']);return $p->scenarios()->create(['name'=>'Costed','organizer_net_monthly_cents'=>0,'organizer_full_monthly_cents'=>0,'costs_complete'=>true]);}
 public function test_unknown_vat_does_not_erase_known_costs_or_cash_requirements(): void {
  $s=$this->scenario();$s->budgetLines()->create(['name'=>'Ground','kind'=>'cost','unit_gross_cents'=>100000,'vat_basis_points'=>null,'deductible'=>true,'forecast_quantity'=>1]);
  $a=$s->eventProject->activities()->create(['name'=>'Game','rules'=>'Test','materials_complete'=>true]);$a->materials()->create(['name'=>'Rental','quantity'=>2,'unit_gross_cents'=>5000,'vat_basis_points'=>null,'deductible'=>true]);$s->includedActivities()->attach($a);
  $f=$s->eventProject->siteFeatures()->create(['name'=>'WC','category'=>'toilet','geometry'=>['type'=>'Point','coordinates'=>[-7.3,40.3]],'needs_complete'=>true]);$f->needs()->create(['name'=>'Cabin','quantity'=>1,'unit_gross_cents'=>20000,'vat_basis_points'=>null,'deductible'=>true]);$s->includedFeatures()->attach($f);
  $r=$s->fresh()->report();$this->assertSame(-130000,$r['forecast_margin_cents']);$this->assertSame(-130000,$r['prepaid_cash_cents']);$this->assertSame(-130000,$r['prepaid_margin_cents']);$this->assertFalse($r['complete']);
 }
 public function test_estimates_cannot_unlock_a_positive_forecast_and_changes_revoke_validation(): void {
  $this->admin();$s=$this->scenario();$s->budgetLines()->create(['name'=>'Cost','kind'=>'cost','unit_gross_cents'=>100,'vat_basis_points'=>0,'forecast_quantity'=>1]);
  $l=$s->budgetLines()->create(['name'=>'Sponsor','kind'=>'revenue','unit_gross_cents'=>100000,'vat_basis_points'=>0,'forecast_quantity'=>1,'committed_quantity'=>1,'paid_quantity'=>1,'pricing_status'=>'estimate']);$l->update(['receipt_reference'=>'TEST','reconciled_at'=>now()]);
  $this->assertFalse($l->verified());$this->assertGreaterThan(0,$s->fresh()->report()['forecast_margin_cents']);$this->assertFalse($s->fresh()->report()['launch_ready']);
  $l->update(['price_source'=>'Contract and invoice','price_checked_at'=>today()->toDateString(),'pricing_status'=>'confirmed']);$l->update(['reconciled_at'=>now()]);$this->assertTrue($l->verified());
  $l->update(['unit_gross_cents'=>120000]);$this->assertSame('estimate',$l->pricing_status);$this->assertFalse($l->verified());$this->assertNull($l->reconciled_at);
  $this->expectException(ValidationException::class);$l->update(['pricing_status'=>'manual']);
 }
 public function test_shared_materials_count_once_and_require_a_common_budget_reference(): void {
  $s=$this->scenario();$s->budgetLines()->create(['name'=>'Broadcast','kind'=>'cost','costing_key'=>'broadcast','unit_gross_cents'=>10000,'vat_basis_points'=>0,'forecast_quantity'=>1]);
  for($i=0;$i<2;$i++){$a=$s->eventProject->activities()->create(['name'=>'Game '.$i,'rules'=>'Test','materials_complete'=>true]);$a->materials()->create(['name'=>'Broadcast share','shared_cost_key'=>'broadcast','quantity'=>1,'unit_gross_cents'=>10000,'vat_basis_points'=>0]);$s->includedActivities()->attach($a);}
  $this->assertSame(-10000,$s->fresh()->report()['forecast_margin_cents']);$s->budgetLines()->first()->update(['forecast_quantity'=>0]);$this->assertStringContainsString('mutualisé absent',implode(' ',$s->fresh()->report()['missing']));
 }
 public function test_seed_has_a_separate_costed_working_scenario_and_preserves_edits(): void {
  $this->seed();$s=Scenario::where('template_key','costing-two-days-v1')->firstOrFail();$r=app(UnitCosting::class)->calculate($s);
  $this->assertCount(12,$s->includedStands);$this->assertSame(2,$s->event_days);$this->assertCount(6,$s->programSlots);$this->assertCount(6,$r['activities']);$this->assertGreaterThan(0,$r['investment_cents']);$this->assertGreaterThan(0,$r['gross_cost_cents']);$this->assertSame(0,$s->budgetLines->sum('committed_quantity'));$this->assertSame(0,$s->budgetLines->sum('paid_quantity'));$this->assertFalse($r['report']['launch_ready']);$this->assertNull($s->organizer_full_monthly_cents);
  $this->assertSame(844994,$r['gross_revenue_cents']);$this->assertSame(1231707,$r['gross_cost_cents']);$this->assertSame(-644523,$r['report']['forecast_margin_cents']);$this->assertLessThan(0,$r['report']['forecast_margin_cents']);
  $independent=collect($r['groups'])->first(fn($g)=>$g['stand']?->kind==='independent');$this->assertSame(16000,$independent['cost']);$this->assertSame(24650,$independent['revenue']-$independent['cost']);
  $line=$s->budgetLines()->where('costing_key','wc')->firstOrFail();$line->update(['unit_gross_cents'=>17500]);$count=$s->budgetLines()->count();$s->update(['event_days'=>3]);$this->seed();$this->assertSame(17500,$line->fresh()->unit_gross_cents);$this->assertSame(3,$s->fresh()->event_days);$this->assertSame($count,$s->budgetLines()->count());
  $this->get('/events/os-jogos-do-agricultor')->assertOk()->assertDontSee('Chiffrage de travail')->assertDontSee('450 €/m³');
 }
 public function test_upgrade_preserves_a_previously_customized_inventory(): void {
  EventProject::create(['name'=>'Os Jogos do Agricultor','slug'=>'os-jogos-do-agricultor']);
  $this->seed(\Database\Seeders\OfficialActivitiesSeeder::class);$this->seed(\Database\Seeders\LaunchModelSeeder::class);$this->seed(\Database\Seeders\HospitalityOperationsSeeder::class);
  $a=Activity::where('template_key','omelete-retro')->firstOrFail();$m=$a->materials()->firstOrFail();$m->update(['quantity'=>12]);
  $this->seed(\Database\Seeders\CostingSeeder::class);$this->assertSame(12,$m->fresh()->quantity);$this->assertNull($m->fresh()->unit_gross_cents);$this->assertSame(1,$a->fresh()->planned_runs);
 }
 public function test_costing_pages_and_related_tables_render_for_active_admin_only(): void {
  $this->seed();$admin=$this->admin();$s=Scenario::where('template_key','costing-two-days-v1')->firstOrFail();
  foreach(['Scenario'=>$s,'Stand'=>$s->includedStands->first(),'Activity'=>$s->includedActivities->first()] as $name=>$record){$class='App\\Filament\\Resources\\'.$name.'Resource';$this->get($class::getUrl('edit',['record'=>$record]))->assertOk();}
  $this->get(\App\Filament\Pages\UnitCosting::getUrl())->assertOk()->assertSee('Chiffrage par unité')->assertSee('À chiffrer');
  $pageClass=\App\Filament\Resources\StandResource\Pages\EditRecord::class;$stand=$s->includedStands->first();
  Livewire::test(CostsRelationManager::class,['ownerRecord'=>$stand,'pageClass'=>$pageClass])->filterTable('scenario_id',$s->id)->assertCanSeeTableRecords($s->budgetLines->where('stand_id',$stand->id)->where('kind','cost'));
  Livewire::test(RevenuesRelationManager::class,['ownerRecord'=>$s,'pageClass'=>\App\Filament\Resources\ScenarioResource\Pages\EditRecord::class])->assertCanSeeTableRecords($s->budgetLines->where('kind','revenue')->take(2));
  Livewire::test(MaterialsRelationManager::class,['ownerRecord'=>$s->includedActivities->first(),'pageClass'=>\App\Filament\Resources\ActivityResource\Pages\EditRecord::class])->assertCanSeeTableRecords($s->includedActivities->first()->materials);
  $admin->update(['is_active'=>false]);$this->get(\App\Filament\Pages\UnitCosting::getUrl())->assertForbidden();
 }
 public function test_stand_grid_creates_scoped_costs_and_rejects_a_foreign_scenario(): void {
  $this->admin();$s=$this->scenario();$stand=$s->eventProject->stands()->create(['name'=>'My stand','kind'=>'independent']);$s->includedStands()->attach($stand);
  $params=['ownerRecord'=>$stand,'pageClass'=>\App\Filament\Resources\StandResource\Pages\EditRecord::class];
  $data=['scenario_id'=>$s->id,'name'=>'Delivery','unit'=>'trajet','expense_type'=>'operating','unit_gross_cents'=>2000,'vat_basis_points'=>2300,'forecast_quantity'=>2,'committed_quantity'=>0,'paid_quantity'=>0,'paid_by'=>'qapas','reimbursed_cents'=>0,'pricing_status'=>'estimate'];
  Livewire::test(CostsRelationManager::class,$params)->callAction(TestAction::make('create')->table(),data:$data)->assertHasNoActionErrors();
  $l=$s->budgetLines()->firstOrFail();$this->assertSame($stand->id,$l->stand_id);$this->assertSame('cost',$l->kind);$this->assertSame('stand',$l->scope);
  Livewire::test(CostsRelationManager::class,$params)->callAction(TestAction::make('edit')->table($l),data:['forecast_quantity'=>3])->assertHasNoActionErrors();$this->assertSame(3,$l->fresh()->forecast_quantity);
  $other=$s->eventProject->scenarios()->create(['name'=>'Not included']);
  Livewire::test(CostsRelationManager::class,$params)->callAction(TestAction::make('create')->table(),data:array_replace($data,['scenario_id'=>$other->id,'name'=>'Forbidden']))->assertHasActionErrors(['scenario_id']);$this->assertSame(1,$s->budgetLines()->count());$this->assertSame(0,$other->budgetLines()->count());
 }
 public function test_stand_grid_never_mixes_scenarios_and_cannot_edit_another_units_row(): void {
  $this->admin();$s=$this->scenario();$stand=$s->eventProject->stands()->create(['name'=>'Mine','kind'=>'independent']);$other=$s->eventProject->stands()->create(['name'=>'Other','kind'=>'independent']);$s->includedStands()->attach([$stand->id,$other->id]);
  $data=['name'=>'Own','kind'=>'cost','scope'=>'stand','stand_id'=>$stand->id,'forecast_quantity'=>1,'unit_gross_cents'=>1000,'vat_basis_points'=>0];$own=$s->budgetLines()->create($data);$foreign=$s->budgetLines()->create(array_replace($data,['stand_id'=>$other->id,'name'=>'Foreign']));
  $second=$s->eventProject->scenarios()->create(['name'=>'Alternative']);$second->includedStands()->attach($stand);$alternative=$second->budgetLines()->create($data);
  $grid=Livewire::test(CostsRelationManager::class,['ownerRecord'=>$stand,'pageClass'=>\App\Filament\Resources\StandResource\Pages\EditRecord::class])->filterTable('scenario_id',$s->id)->assertCanSeeTableRecords([$own])->assertCanNotSeeTableRecords([$foreign,$alternative]);
  $grid->filterTable('scenario_id',null)->assertCanNotSeeTableRecords([$own,$foreign,$alternative]);
  $grid->filterTable('scenario_id',$s->id);
  try{$grid->callAction(TestAction::make('edit')->table($foreign),data:['name'=>'Hacked']);$this->fail('A foreign row must not resolve in this relation.');}catch(\Filament\Actions\Exceptions\ActionNotResolvableException $e){$this->assertStringContainsString('no longer exists',$e->getMessage());}
  $this->assertSame('Foreign',$foreign->fresh()->name);
 }
}
