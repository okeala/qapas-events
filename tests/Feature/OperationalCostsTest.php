<?php
namespace Tests\Feature;
use App\Domain\Finance\{TransportCost,FinancialSources};
use App\Domain\Procurement\Consultations;
use App\Models\{Admin,EventProject,FinancialPlan,CostConsultation,CabinProject,CupPlan,SiteInfrastructure};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class OperationalCostsTest extends TestCase {
 use RefreshDatabase;
 public function test_transport_math_includes_three_round_trips_and_handling_without_a_second_salary(): void {
  $r=TransportCost::calculate(['round_trips'=>3,'one_way_km'=>35,'one_way_minutes'=>35,'handling_minutes_per_trip'=>60,'fuel_ml_per_100km'=>12000,'fuel_cents_per_litre'=>180]);
  $this->assertSame(['km'=>210,'fuel_ml'=>25200,'fuel_cents'=>4536,'minutes'=>390],$r);
  $this->expectException(\Illuminate\Validation\ValidationException::class);TransportCost::calculate(['round_trips'=>0]);
 }
 public function test_operational_revision_replaces_aggregates_and_distinguishes_forecasts_from_payments(): void {
  $this->seed();$p=EventProject::first();$s=$p->launchScenario();$line=fn($key)=>$s->budgetLines()->where('costing_key',$key)->sole();
  $this->assertSame('rental-logistics-2026-v1',$p->operational_costs_version);
  foreach(['cabins-shredder-purchase','cabins-shared-logistics','palheira-septic'] as $key)$this->assertSame(0,$line($key)->forecast_quantity);
  $this->assertSame(10000,$line('stands-shredder-rental')->unit_gross_cents);$this->assertSame('operating',$line('stands-shredder-rental')->expense_type);
  $this->assertSame(20,$line('stands-shredder-sp98')->forecast_quantity);$this->assertSame(4536,$line('stands-dyna-tubes')->unit_gross_cents);
  $this->assertSame(7,$line('wc')->forecast_quantity);$this->assertSame(1,$line('wc-pmr')->forecast_quantity);
  $this->assertSame(5000,$line('insurance')->unit_gross_cents);$this->assertNull($line('insurance')->vat_basis_points);
  $this->assertSame(400,$line('soup-ingredients')->forecast_quantity);$this->assertSame(8,$line('soup-bowls')->forecast_quantity);$this->assertSame(881,$line('soup-bowls')->unit_gross_cents);$this->assertSame(300,$line('soup-sales')->forecast_quantity);
  $this->assertSame(3,$line('coffee-machines')->forecast_quantity);$this->assertSame(16,$line('coffee-ground')->forecast_quantity);$this->assertSame(10,$line('coffee-sugar')->forecast_quantity);
  $this->assertSame(16,$s->budgetLines()->where('costing_key','like','stand-extension-%')->count());$this->assertSame(3,$line('stands-electric-boxes')->forecast_quantity);
  $this->assertSame(0,(int)$s->budgetLines()->sum('paid_quantity'));$this->assertSame(0,(int)$s->budgetLines()->sum('committed_quantity'));
  $this->assertFalse($s->report()['launch_ready']);$this->assertSame('qapas_disposable',CupPlan::where('scenario_id',$s->id)->sole()->soup_bowl_owner);
  $pin=$p->activities()->where('template_key','omelete-retro')->sole()->materials()->where('name','like','Pince fermante%')->sole();$this->assertSame(27999,$pin->unit_gross_cents);$this->assertSame('fixed',$pin->basis);$this->assertSame(1,$pin->quantity);
  $this->assertSame(3,collect(CabinProject::first()->construction_costs)->firstWhere('name','Sisal naturel — trois bobines par stand')['quantity']);
  foreach(SiteInfrastructure::where('template_key','like','extension-%')->get() as $i){$this->assertFalse($i->available());$this->assertSame('declared',$i->status);}
  $rentalRequest=app(Consultations::class)->ensure($line('stands-shredder-rental'));$this->assertStringContainsString('Location une journée REMO',$rentalRequest->body_fr);$this->assertStringContainsString('Aluguer REMO',$rentalRequest->body_pt);$this->assertNull($rentalRequest->sent_at);
  $wcRequest=app(Consultations::class)->ensure($line('wc'));$this->assertStringContainsString('Quantité prévue : 7',$wcRequest->body_fr);$this->assertStringContainsString('500',$wcRequest->body_pt);
  $plan=FinancialPlan::where('scenario_id',$s->id)->sole();$this->assertSame('capex',$plan->profiles()->where('source_type','budget')->where('source_id',$line('stands-electric-boxes')->id)->sole()->category);
 }
 public function test_reseeding_preserves_custom_prices_drafts_and_bom_without_duplicate_costs(): void {
  $this->seed();$p=EventProject::first();$s=$p->launchScenario();$count=$s->budgetLines()->count();$cost=$s->budgetLines()->where('costing_key','insurance')->sole();
  $cost->update(['unit_gross_cents'=>9750,'price_source'=>'Personal quote']);$c=app(Consultations::class)->ensure($cost);$c->update(['body_fr'=>'My draft','sent_at'=>now()]);
  $construction=CabinProject::first();$rows=$construction->construction_costs;$rows[0]['unit_cents']=200;$construction->update(['construction_costs'=>$rows]);
  $this->seed();$this->assertSame($count,$s->budgetLines()->count());$this->assertSame(9750,$cost->fresh()->unit_gross_cents);$this->assertSame('My draft',$c->fresh()->body_fr);$this->assertSame(200,$construction->fresh()->construction_costs[0]['unit_cents']);
  // Even a forced revision retry cannot overwrite a sent consultation or a personalized price.
  $p->update(['operational_costs_version'=>null]);$this->seed(\Database\Seeders\OperationalCostsSeeder::class);$this->assertSame(9750,$cost->fresh()->unit_gross_cents);$this->assertSame('My draft',$c->fresh()->body_fr);$this->assertSame($count,$s->budgetLines()->count());
 }
 public function test_updated_financial_forms_remain_private_and_render(): void {
  $this->seed();$p=EventProject::first();$s=$p->launchScenario();$url=\App\Filament\Resources\ScenarioResource::getUrl('edit',['record'=>$s]);$this->get($url)->assertRedirect();
  $admin=Admin::create(['name'=>'Ops','email'=>'ops@example.test','password'=>'long-password-for-test']);$this->actingAs($admin,'admin');$this->get($url)->assertOk();
  $cup=CupPlan::where('scenario_id',$s->id)->sole();\Livewire\Livewire::test(\App\Filament\Resources\CupPlanResource\Pages\ListRecords::class)->assertOk();
  $line=$s->budgetLines()->where('costing_key','stands-shredder-rental')->sole();$c=app(Consultations::class)->ensure($line);$this->get(\App\Filament\Resources\CostConsultationResource::getUrl('edit',['record'=>$c]))->assertOk();
  $admin->update(['is_active'=>false]);$this->get($url)->assertForbidden();
 }
}
