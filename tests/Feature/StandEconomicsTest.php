<?php
namespace Tests\Feature;
use App\Domain\Stands\{ConstructionCosting,StandEconomics};
use App\Filament\Resources\StandResource;
use App\Filament\RelationManagers\StandExternalLinesRelationManager;
use App\Models\{Admin,EventProject,CabinProject,StandExternalLine};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;
class StandEconomicsTest extends TestCase {
 use RefreshDatabase;
 private function fixture(bool $rental=false): array {
  $p=EventProject::create(['name'=>'Jeux','slug'=>'unit-test']);$s=$p->scenarios()->create(['name'=>'Pilot','template_key'=>'costing-rental-experts-v1']);$stand=$p->stands()->create(['name'=>'Stand A','kind'=>$rental?'independent':'village']);$s->includedStands()->attach($stand);
  $c=CabinProject::create(['event_project_id'=>$p->id,'stand_id'=>$stand->id,'name'=>'Construction A','supply_mode'=>$rental?'qapas_rental':'team_build','construction_costs'=>ConstructionCosting::defaults()]);return [$p,$s,$stand,$c];
 }
 private function admin(): Admin {$a=Admin::create(['name'=>'Manager','email'=>'stands@test.tld','password'=>'test-password-long']);$this->actingAs($a,'admin');return $a;}
 private function invalid(callable $f): void {try{$f();$this->fail('Expected validation');}catch(ValidationException){$this->assertTrue(true);}}
 public function test_nomenclature_corrects_lengths_and_prices_without_inventing_a_complete_cost(): void {
  $r=ConstructionCosting::report(ConstructionCosting::defaults());$this->assertSame(17,$r['tube_count']);$this->assertSame(40240,$r['tube_length_mm']);$this->assertSame(20120,$r['groups']['tubes']['reference']);$this->assertSame(21500,$r['groups']['connectors']['reference']);$this->assertSame(3660,$r['groups']['roof_fixings']['reference']);$this->assertSame(45280,$r['reference_known_cents']);$this->assertSame(6,$r['reference_missing']);$this->assertNull($r['planned_cents']);$this->assertSame(0,$r['planned_known_cents']);
  $rows=ConstructionCosting::defaults();$rows[0]['unit_cents']=100;$r=ConstructionCosting::report($rows);$this->assertSame(960,$r['planned_known_cents']);$this->assertNull($r['planned_cents']);$rows[0]['unit_cents']='';$this->assertNull(ConstructionCosting::report($rows)['planned_cents']);$rows[0]['length_mm']=null;$this->invalid(fn()=>ConstructionCosting::report($rows));
 }
 public function test_participant_sales_and_costs_never_change_qapas_break_even(): void {
  [$p,$s,$stand]=$this->fixture();$cost=$s->budgetLines()->create(['name'=>'QAPAS setup','kind'=>'cost','scope'=>'stand','stand_id'=>$stand->id,'unit_gross_cents'=>10000,'vat_basis_points'=>0,'forecast_quantity'=>1]);$sale=$s->budgetLines()->create(['name'=>'QAPAS rental','kind'=>'revenue','scope'=>'stand','stand_id'=>$stand->id,'unit_gross_cents'=>20000,'vat_basis_points'=>0,'forecast_quantity'=>1]);
  $before=$s->fresh()->report();
  foreach([['Stock','cost',5000],['Fromages vendus','revenue',9000]] as [$name,$kind,$amount])$stand->externalLines()->create(['scenario_id'=>$s->id,'party'=>'exhibitor','holder'=>'Maria','name'=>$name,'kind'=>$kind,'quantity'=>1,'unit_gross_cents'=>$amount]);
  $this->assertEquals($before,$s->fresh()->report());$r=app(StandEconomics::class)->report($stand,$s);$this->assertSame(10000,$r['qapas']['balance_cents']);$this->assertSame(4000,$r['external'][0]['balance_cents']);
  $line=$stand->externalLines()->create(['scenario_id'=>$s->id,'party'=>'team','holder'=>'Équipe A','name'=>'Mobilier à louer','kind'=>'cost','quantity'=>1]);$r=app(StandEconomics::class)->report($stand,$s);$this->assertNull($r['external'][1]['balance_cents']);$this->assertSame(1,$r['external'][1]['cost_missing']);
  $line->update(['quantity'=>0]);$this->assertSame(0,$line->fresh()->total());
  $this->invalid(fn()=>$line->update(['quantity'=>1,'qapas_budget_line_id'=>$cost->id]));$line->fresh()->update(['scenario_id'=>(string)$s->id,'qapas_budget_line_id'=>(string)$sale->id]);$this->invalid(fn()=>$sale->update(['kind'=>'cost']));
  $other=$p->scenarios()->create(['name'=>'Unrelated']);$this->invalid(fn()=>$line->fresh()->update(['scenario_id'=>$other->id]));$this->invalid(fn()=>app(StandEconomics::class)->report($stand,$other));
 }
 public function test_construction_budget_is_explicit_idempotent_and_routed_to_its_real_payer(): void {
  $this->admin();[$p,$s,$stand,$c]=$this->fixture();$service=app(ConstructionCosting::class);$this->invalid(fn()=>$service->apply($c,$s->id));
  $rows=[['name'=>'Construction complète','group'=>'completion','quantity'=>1,'basis'=>'lot','unit_cents'=>45000,'source'=>'service']];
  $c->update(['construction_costs'=>$rows,'construction_price_basis'=>'gross','construction_vat_basis_points'=>2300]);$service->apply($c,$s->id);$service->apply($c,$s->id);
  $this->assertDatabaseCount('stand_external_lines',1);$this->assertDatabaseCount('budget_lines',0);$this->assertSame(45000,$stand->externalLines()->sole()->total());
  $qapasStand=$p->stands()->create(['name'=>'Stand loué','kind'=>'independent']);$s->includedStands()->attach($qapasStand);$line=$s->budgetLines()->create(['name'=>'Construction QAPAS','kind'=>'cost','scope'=>'stand','stand_id'=>$qapasStand->id,'forecast_quantity'=>1]);
  $q=CabinProject::create(['name'=>'Dossier QAPAS','event_project_id'=>$p->id,'stand_id'=>$qapasStand->id,'supply_mode'=>'qapas_rental','cost_line_id'=>$line->id,'construction_costs'=>$rows,'construction_price_basis'=>'net','construction_vat_basis_points'=>2300]);
  $this->invalid(fn()=>$service->apply($q,$s->id));$q->update(['construction_price_basis'=>'gross']);$service->apply($q,$s->id);$service->apply($q,$s->id);$this->assertDatabaseCount('budget_lines',1);$this->assertSame(45000,$line->fresh()->unit_gross_cents);$this->assertSame(0,$line->fresh()->paid_quantity);$this->assertSame('estimate',$line->fresh()->pricing_status);
  $line->fresh()->update(['committed_quantity'=>1]);$this->invalid(fn()=>$service->apply($q,$s->id));$this->assertSame(45000,$line->fresh()->unit_gross_cents);
  auth('admin')->user()->update(['is_active'=>false]);$this->assertFalse(Gate::forUser(auth('admin')->user())->allows('create',StandExternalLine::class));$this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);$service->apply($c,$s->id);
 }
 public function test_private_stand_forms_render_and_external_lines_validate_on_the_server(): void {
  $a=$this->admin();[$p,$s,$stand]=$this->fixture();$this->get(StandResource::getUrl('edit',['record'=>$stand]))->assertOk()->assertSee('unité de coûts');
  Livewire::test(StandExternalLinesRelationManager::class,['ownerRecord'=>$stand,'pageClass'=>\App\Filament\Resources\StandResource\Pages\EditRecord::class])->assertOk()->callAction(\Filament\Actions\Testing\TestAction::make('create')->table(),data:['scenario_id'=>$s->id,'party'=>'exhibitor','holder'=>'João','name'=>'Produits du terroir','kind'=>'revenue','quantity'=>100,'unit'=>'lot','unit_gross_cents'=>500])->assertHasNoActionErrors();
  $this->assertSame(50000,$stand->externalLines()->sole()->total());$this->assertDatabaseCount('budget_lines',0);$a->update(['is_active'=>false]);$this->get(StandResource::getUrl('edit',['record'=>$stand]))->assertForbidden();
 }
 public function test_seed_adds_only_a_working_estimate_and_preserves_existing_edits(): void {
  $this->seed();$c=CabinProject::firstOrFail();$this->assertSame(45280,ConstructionCosting::report($c->construction_costs)['reference_known_cents']);$this->assertNull(ConstructionCosting::report($c->construction_costs)['planned_cents']);$this->assertSame('unknown',$c->construction_price_basis);$this->assertDatabaseCount('stand_external_lines',0);
  $rows=$c->construction_costs;$rows[0]['unit_cents']=80;$c->update(['construction_costs'=>$rows]);$this->seed(\Database\Seeders\StandEconomicsSeeder::class);$this->assertSame(80,$c->fresh()->construction_costs[0]['unit_cents']);
 }
}
