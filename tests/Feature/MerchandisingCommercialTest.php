<?php
namespace Tests\Feature;
use App\Models\{Admin,EventProject,WelcomePackPlan,MerchandisingOption,CommercialPlan,EventBadge};
use App\Domain\Finance\{CommercialPricing,OrganizerCost};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class MerchandisingCommercialTest extends TestCase {
 use RefreshDatabase;
 private function admin(): void {$this->actingAs(Admin::firstOrCreate(['email'=>'merch@example.test'],['name'=>'Admin','password'=>'long-merchandising-password']),'admin');}
 private function invalid(callable $f): void {try{$f();$this->fail('Validation expected');}catch(ValidationException){$this->assertTrue(true);}}
 private function seedPlan(): CommercialPlan {$this->travelTo(\Carbon\Carbon::parse('2026-10-01'));$this->seed();$this->admin();return CommercialPlan::firstOrFail();}
 public function test_seed_prices_cover_modeled_cash_and_target_but_never_claim_paid_breakeven(): void {
  $plan=$this->seedPlan();$r=$plan->report();$s=$plan->scenario;
  $this->assertDatabaseCount('merchandising_options',4);$this->assertDatabaseCount('commercial_plans',1);$this->assertDatabaseCount('event_badges',0);
  $this->assertSame(80000,$s->minimum_organizer_charges_cents);$this->assertSame(0,$s->organizer_net_monthly_cents);$this->assertNull($s->organizer_full_monthly_cents);$this->assertSame(80000,OrganizerCost::amount($s));
  $this->assertGreaterThanOrEqual(0,$r['headroom_cents']);$this->assertGreaterThanOrEqual($r['village_break_even_cents'],$r['village_unit_cents']);$this->assertGreaterThan($r['village_unit_cents'],$r['village_without_sponsors_cents']);$this->assertNotEmpty($r['issues']);$this->assertFalse($s->report()['launch_ready']);$this->assertSame(0,$s->budgetLines->sum('paid_quantity'));
  $lines=app(CommercialPricing::class)->lines($plan);$this->assertCount(12,$lines);foreach($lines as $line)$this->assertSame($line->stand->kind==='village'?$r['village_unit_cents']:65000,$line->unit_gross_cents);
  $this->assertGreaterThanOrEqual($s->target_surplus_cents*$s->months,$s->report()['forecast_margin_cents']);
  $plan->update(['independent_price_cents'=>70000]);$this->seed();$this->assertSame(70000,$plan->fresh()->independent_price_cents);$this->assertDatabaseCount('merchandising_options',4);
 }
 public function test_charges_are_never_free_or_double_counted_and_opportunity_is_not_cash(): void {
  $plan=$this->seedPlan();$s=$plan->scenario;$s->update(['months'=>2,'organizer_full_monthly_cents'=>100000]);$this->assertSame(200000,OrganizerCost::amount($s));$s->update(['organizer_full_monthly_cents'=>0]);$this->assertSame(80000,OrganizerCost::amount($s));
  $o=MerchandisingOption::where('method','inhouse')->first();$r=$o->report();$this->assertSame(132,$r['quantity']);$this->assertGreaterThan(130,$r['machine_payback_quantity']);$this->assertSame($r['cash_cents']+$r['opportunity_cents'],$r['economic_cents']);$cost=$r['cash_cents'];$o->update(['opportunity_hourly_cents'=>5000]);$this->assertSame($cost,$o->report()['cash_cents']);$o->update(['external_fixed_cents'=>null]);$this->assertNull($o->report()['machine_payback_quantity']);$o->update(['external_fixed_cents'=>10000,'external_unit_cents'=>1]);$this->assertNull($o->report()['machine_payback_quantity']);
  $this->invalid(fn()=>$s->update(['minimum_organizer_charges_cents'=>-1]));$s->refresh();$this->invalid(fn()=>$s->update(['minimum_organizer_charges_cents'=>0]));
 }
 public function test_tariff_solver_excludes_uncertain_onsite_sales_and_preserves_signed_prices(): void {
  $plan=$this->seedPlan();$before=$plan->report()['village_unit_cents'];$line=$plan->scenario->budgetLines()->where('costing_key','bar-sales')->first();$line->update(['unit_gross_cents'=>999999]);$this->assertSame($before,$plan->fresh()->report()['village_unit_cents']);
  $line=app(CommercialPricing::class)->lines($plan)->first();$line->update(['committed_quantity'=>1]);$this->invalid(fn()=>app(CommercialPricing::class)->apply($plan));$this->assertSame($line->unit_gross_cents,$line->fresh()->unit_gross_cents);
  auth('admin')->logout();try{app(CommercialPricing::class)->apply($plan);$this->fail('Forbidden');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(403,$e->getStatusCode());}
 }
 private function badge(): EventBadge {
  $plan=$this->seedPlan();$p=$plan->scenario->eventProject;$p->update(['is_public'=>true]);$pack=WelcomePackPlan::first();$pack->update(['people'=>[['person_key'=>'private-register-42','name'=>'PRIVATE PERSON','category'=>'qapas','size'=>'XL','confirmed'=>true]]]);
  return EventBadge::create(['event_project_id'=>$p->id,'welcome_pack_plan_id'=>$pack->id,'person_key'=>'private-register-42','role'=>'qapas','status'=>'active','valid_from'=>now()->subDay(),'valid_until'=>now()->addDay(),'evidence'=>'PRIVATE EVIDENCE, assignment verified']);
 }
 public function test_badge_projection_is_private_and_rechecks_expiry_and_revocation(): void {
  $b=$this->badge();auth('admin')->logout();$response=$this->get($b->url())->assertOk()->assertHeader('Cache-Control','no-store, private');$response->assertSee($b->serial)->assertDontSee('PRIVATE PERSON')->assertDontSee('private-register-42')->assertDontSee('PRIVATE EVIDENCE');
  $this->get(route('badge.print',['badge'=>$b->public_id]))->assertForbidden();$this->admin();$this->get(route('badge.print',['badge'=>$b->public_id]))->assertOk()->assertSee('<svg',false)->assertDontSee('PRIVATE PERSON');
  $b->update(['status'=>'revoked','revocation_reason'=>'PRIVATE REASON']);$this->assertFalse($b->fresh()->valid());$this->get($b->url())->assertOk()->assertDontSee('PRIVATE REASON');$this->invalid(fn()=>$b->update(['status'=>'active']));
  $replacement=$b->replicate(['public_id','serial','status','revoked_at','revocation_reason']);$replacement->status='draft';$replacement->save();$this->assertNotSame($b->public_id,$replacement->public_id);
 }
 public function test_badge_is_not_transferable_or_valid_for_a_removed_holder_or_fake_player(): void {
  $b=$this->badge();$duplicate=$b->replicate(['public_id','serial']);$this->invalid(fn()=>$duplicate->save());$this->invalid(fn()=>$b->update(['person_key'=>'another']));$b->refresh();$pack=$b->welcomePackPlan;$pack->update(['people'=>[]]);$this->assertFalse($b->fresh()->valid());$this->travel(3)->days();$this->assertFalse($b->fresh()->valid());
  $pack->update(['people'=>[['person_key'=>'fake-player','name'=>'Unselected private person','category'=>'players','size'=>'L','confirmed'=>true]]]);$this->invalid(fn()=>EventBadge::create(['event_project_id'=>$b->event_project_id,'welcome_pack_plan_id'=>$pack->id,'person_key'=>'fake-player','role'=>'players','status'=>'active','valid_from'=>now()->subDay(),'valid_until'=>now()->addDay(),'evidence'=>'A roster entry alone is not an election result']));
  $b->eventProject->update(['is_public'=>false]);$this->get($b->url())->assertNotFound();
 }
 public function test_merchandising_and_badge_forms_and_comparison_views_render_for_admin_only(): void {
  $plan=$this->seedPlan();foreach(['CommercialPlan','MerchandisingOption','EventBadge'] as $name){$resource='App\\Filament\\Resources\\'.$name.'Resource';$this->get($resource::getUrl())->assertOk();\Livewire\Livewire::test($resource.'\\Pages\\ManageRecords')->mountAction('create')->assertHasNoActionErrors();}
  $prices=\Livewire\Livewire::test(\App\Filament\Resources\CommercialPlanResource\Pages\ManageRecords::class)->mountAction(\Filament\Actions\Testing\TestAction::make('report')->table($plan))->assertActionMounted(\Filament\Actions\Testing\TestAction::make('report')->table($plan))->assertSuccessful();
  $this->assertStringContainsString('PRÉVISION CONDITIONNELLE',$prices->instance()->getMountedAction()->getModalContent()->render());
  $comparison=\Livewire\Livewire::test(\App\Filament\Resources\MerchandisingOptionResource\Pages\ManageRecords::class)->mountAction(\Filament\Actions\Testing\TestAction::make('compare')->table(MerchandisingOption::first()))->assertActionMounted(\Filament\Actions\Testing\TestAction::make('compare')->table(MerchandisingOption::first()))->assertSuccessful();
  $this->assertStringContainsString('Alternatives de travail',$comparison->instance()->getMountedAction()->getModalContent()->render());
 }
}
