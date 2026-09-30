<?php
namespace Tests\Feature;
use App\Models\{Admin,EventProject,CommunityAward,Sponsorship,WelcomePackPlan,BudgetLine,Idea,Team,TeamRoleAssignment};
use App\Domain\Awards\Forquilha;
use App\Domain\Planning\Readiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class RecognitionSponsorshipTest extends TestCase {
 use RefreshDatabase;
 private function admin(): void {$this->actingAs(Admin::firstOrCreate(['email'=>'recognition@example.test'],['name'=>'Admin','password'=>'long-recognition-password']),'admin');}
 private function invalid(callable $callback): void {try{$callback();$this->fail('Validation expected');}catch(ValidationException){$this->assertTrue(true);}}
 private function seedPlan(): WelcomePackPlan {$this->travelTo(\Carbon\Carbon::parse('2026-10-01'));$this->seed();$this->admin();return WelcomePackPlan::firstOrFail();}
 private function receive(int $gross): CommunityAward {
  $award=CommunityAward::firstOrFail();$sp=$award->prizeSponsor;$line=$sp->budgetLine;
  $sp->update(['status'=>'agreed','sponsor_name'=>'Parrain du prix','agreed_at'=>now(),'agreement_evidence'=>'Signed contract','web_rights'=>'Web role only','cash_pledged_cents'=>$gross]);
  $line->update(['unit_gross_cents'=>$gross,'vat_basis_points'=>2300,'committed_quantity'=>1,'paid_quantity'=>1,'price_source'=>'Reviewed invoice','price_checked_at'=>today(),'pricing_status'=>'confirmed']);
  if($line->pricing_status!=='confirmed')$line->update(['pricing_status'=>'confirmed']);
  $line->update(['receipt_reference'=>'prize-bank','reconciled_at'=>now()]);return $award->fresh();
 }
 public function test_seed_reuses_prize_and_creates_five_unfunded_sponsor_roles_once(): void {
  $pack=$this->seedPlan();$this->assertDatabaseCount('community_awards',1);$this->assertDatabaseCount('sponsorships',15);$this->assertDatabaseCount('welcome_pack_plans',1);
  $p=$pack->eventProject;$this->assertSame(1,$p->launchScenario()->budgetLines()->where('costing_key','community-christmas-prize')->count());$this->assertSame(50000,CommunityAward::first()->budgetLine->unit_gross_cents);
  $this->assertFalse(app(Forquilha::class)->report($p)['secured']);$this->assertCount(4,Sponsorship::where('scope','award')->get());
  $pack->update(['name'=>'My own lot']);$this->seed();$this->assertSame('My own lot',$pack->fresh()->name);$this->assertDatabaseCount('sponsorships',15);
  $prefund=$p->ideas()->where('template_key','forquilha-prefund')->first();$open=$p->ideas()->where('template_key','presales-open')->first();$this->assertContains($prefund->id,$open->depends_on);$this->assertLessThan($open->sort_order,$prefund->sort_order);
 }
 public function test_prize_requires_five_hundred_net_received_and_an_explicit_reserve(): void {
  $pack=$this->seedPlan();$service=app(Forquilha::class);$a=$this->receive(50000);$this->assertSame(40650,$service->receivedNet($a));$this->invalid(fn()=>$service->reserve($a,'Reserve evidence'));
  $a=$this->receive(61500);$this->assertSame(50000,$service->receivedNet($a));$this->assertFalse($service->report($a->eventProject)['secured']);$service->reserve($a,'500 euros separately allocated to the prize');$this->assertTrue($service->report($a->eventProject)['secured']);
  $this->assertStringNotContainsString('500 € encaissés',implode(' ',app(Readiness::class)->blockers($a->eventProject,'registration')));
  $line=$a->prizeSponsor->budgetLine;$line->update(['paid_quantity'=>0]);$this->assertFalse($service->report($a->eventProject)['secured']);$this->assertStringContainsString('500 € encaissés',implode(' ',app(Readiness::class)->blockers($a->eventProject,'registration')));
 }
 public function test_reservation_cannot_be_faked_and_production_is_a_separate_condition(): void {
  $pack=$this->seedPlan();$a=CommunityAward::first();$this->invalid(fn()=>$a->update(['reserved_at'=>now(),'reserved_by'=>auth('admin')->id(),'reserve_evidence'=>'No cash','reserve_fingerprint'=>str_repeat('a',64)]));
  $a=$this->receive(61500);auth('admin')->logout();try{app(Forquilha::class)->reserve($a,'No admin');$this->fail('403 expected');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(403,$e->getStatusCode());}$this->admin();app(Forquilha::class)->reserve($a,'Evidence');
  $this->assertFalse(app(Forquilha::class)->report($a->eventProject)['production_ready']);
  foreach(Sponsorship::where('scope','award')->where('purpose','!=','prize')->get() as $sp){$this->invalid(fn()=>$sp->update(['delivered_at'=>now(),'delivery_evidence'=>'Uncontracted']));$sp->refresh();$line=$sp->deliveryCostLine;$line->update(['unit_gross_cents'=>0,'vat_basis_points'=>0,'pricing_status'=>'confirmed','price_source'=>'Signed in-kind contribution with no remaining QAPAS cost','price_checked_at'=>today()]);$sp->update(['status'=>'agreed','sponsor_name'=>'Artist for '.$sp->purpose,'agreed_at'=>now(),'agreement_evidence'=>'Signed','web_rights'=>'Role credit','in_kind'=>'Complete delivery','delivery_due_at'=>now()->addDays(20)]);$sp->update(['delivered_at'=>now(),'delivery_evidence'=>'Accepted deliverable and images']);$this->assertTrue($sp->fresh()->deliveryComplete());}
  $this->assertTrue(app(Forquilha::class)->report($a->eventProject)['production_ready']);$line->update(['unit_gross_cents'=>100]);$this->assertFalse(app(Forquilha::class)->report($a->eventProject)['production_ready']);
 }
 public function test_sponsor_roles_are_exclusive_and_cannot_link_another_editions_cost(): void {
  $pack=$this->seedPlan();$a=$this->receive(61500);$copy=$a->prizeSponsor->replicate(['public_id','template_key','budget_line_id']);$this->invalid(fn()=>$copy->save());
  $other=EventProject::create(['name'=>'Other','slug'=>'other']);$sc=$other->scenarios()->create(['name'=>'Budget']);$cost=$sc->budgetLines()->create(['name'=>'Other cost','kind'=>'cost']);$this->invalid(fn()=>$pack->sponsor->update(['delivery_cost_line_id'=>$cost->id]));
  $this->invalid(fn()=>$a->update(['prize_sponsorship_id'=>$pack->sponsor_id]));
 }
 public function test_prize_milestone_and_announcement_cannot_be_validated_on_a_promise(): void {
  $pack=$this->seedPlan();$step=Idea::where('template_key','forquilha-prefund')->first();$this->invalid(fn()=>$step->update(['status'=>'validated','owner'=>'QAPAS','evidence'=>'Promise','depends_on'=>[]]));$step->refresh();
  $a=$this->receive(61500);app(Forquilha::class)->reserve($a,'Allocated');$step->update(['status'=>'validated','owner'=>'QAPAS','evidence'=>'Cash received','depends_on'=>[]]);$this->assertTrue($step->isValidated());
  $a->prizeSponsor->update(['agreement_evidence'=>'Changed contract']);$this->assertFalse($step->fresh()->isValidated());$press=$pack->eventProject->pressReleases()->where('publication_version','draft-prize-v1')->first();$this->assertContains('Jalon préalable non validé.',$press->publicationProblems());
 }
 public function test_shirt_plan_has_exactly_twenty_percent_global_reserve_and_no_price_claim(): void {
  $p=$this->seedPlan();$r=$p->report();$this->assertSame(108,$r['people']);$this->assertSame(22,$r['reserve']);$this->assertSame(130,$r['total']);$this->assertSame(130,array_sum(array_column($r['sizes'],'total')));$this->assertSame(['XS'=>5,'S'=>17,'M'=>38,'L'=>41,'XL'=>22,'XXL'=>7,'3XL'=>0,'4XL'=>0],array_map(fn($v)=>$v['total'],$r['sizes']));$this->assertSame(137500,$r['estimated_cost_cents']);$this->assertNull($r['sponsor_requirement_cents']);$this->assertFalse($r['cost_complete']);
  $p->update(['shirts_per_person'=>2]);$this->assertSame(260,$p->report()['total']);$this->assertFalse($p->report()['cost_complete']);$p->synchronizeBudget();$this->assertSame(260,$p->shirtCostLine->fresh()->forecast_quantity);$p->shirtCostLine->update(['committed_quantity'=>1]);$this->invalid(fn()=>$p->synchronizeBudget());
 }
 public function test_roster_duplicates_sizes_and_approval_are_enforced(): void {
  $p=$this->seedPlan();$person=['person_key'=>'register-1','name'=>'One person','category'=>'qapas','size'=>'M','confirmed'=>true];$this->invalid(fn()=>$p->update(['people'=>[$person,array_replace($person,['person_key'=>' REGISTER-1 '])]]));$p->refresh();
  $this->invalid(fn()=>$p->update(['approved_at'=>now(),'approval_evidence'=>'Only a forecast']));$p->refresh();$p->update(['use_roster'=>true,'people'=>[$person]]);$r=$p->report();$this->assertSame(1,$r['base']);$this->assertSame(1,$r['reserve']);$this->assertSame(2,$r['total']);
  $p->update(['approved_at'=>now(),'approval_evidence'=>'Person confirmed manufacturer size chart']);$this->assertNotNull($p->fresh()->approved_at);$p->update(['people'=>[array_replace($person,['size'=>'L'])]]);$this->assertNull($p->fresh()->approved_at);
  $p->update(['people'=>[array_replace($person,['size'=>null,'confirmed'=>false])]]);$this->assertFalse($p->report()['sizes_ready']);$this->invalid(fn()=>$p->update(['approved_at'=>now(),'approval_evidence'=>'Missing size']));
 }
 public function test_import_counts_a_shared_excavator_once_and_keeps_staff_sizes(): void {
  $this->admin();$event=EventProject::create(['name'=>'Elected teams','slug'=>'elected-teams','community_version'=>'test']);
  foreach(['A','B'] as $name){$team=$event->teams()->create(['name'=>$name,'freguesia'=>$name]);foreach(\App\Domain\Teams\ExpertRoles::CORE as $code){$shared=$code==='excavator_operator';$ref=$shared?'operator-shared':$name.'-'.$code;$slot=$team->roleAssignments()->where('role_code',$code)->first();$slot->update(['candidate_name'=>$ref,'candidate_reference'=>$ref,'status'=>'confirmed','consent_confirmed'=>true,'competence_evidence'=>'Verified','shared_operator'=>$shared,'sharing_evidence'=>$shared?'Both teams agreed; different schedules':null]);}$team->update(['status'=>'elected','election_minutes'=>'Local vote documented']);}
  $pack=WelcomePackPlan::create(['event_project_id'=>$event->id,'name'=>'People','cohorts'=>[['category'=>'players','quantity'=>12]],'sizes'=>[['size'=>'M','quantity'=>12]],'people'=>[['person_key'=>'register-operator-shared','name'=>'Operator','category'=>'service','size'=>'XL','confirmed'=>true]]]);
  $this->assertCount(11,$pack->electedPeople());$pack->importPlayers();$pack->refresh();$this->assertCount(11,$pack->people);$this->assertSame('XL',collect($pack->people)->firstWhere('person_key','register-operator-shared')['size']);$pack->importPlayers();$this->assertCount(11,$pack->fresh()->people);
 }
 public function test_shirt_sponsor_covers_only_verified_cash_and_distinct_costs(): void {
  $p=$this->seedPlan();foreach(['shirtCostLine','setupCostLine','deliveryCostLine'] as $relation){$line=$p->$relation;$line->update(['vat_basis_points'=>2300,'price_source'=>'Supplier signed quote','price_checked_at'=>today(),'pricing_status'=>'confirmed']);}
  $r=$p->fresh()->report();$this->assertTrue($r['cost_complete']);$this->assertSame(137500,$r['sponsor_requirement_cents']);$this->assertSame(137500,$r['funding_gap_cents']);
  $sp=$p->sponsor;$sp->update(['status'=>'agreed','sponsor_name'=>'Shirts partner','agreed_at'=>now(),'agreement_evidence'=>'Signed','web_rights'=>'Web and shirt BAT','cash_pledged_cents'=>169125]);$this->assertSame(137500,$p->fresh()->report()['funding_gap_cents']);$line=$sp->budgetLine;$line->update(['unit_gross_cents'=>169125,'vat_basis_points'=>2300,'committed_quantity'=>1,'paid_quantity'=>1,'pricing_status'=>'confirmed','price_source'=>'Reviewed contract','price_checked_at'=>today()]);$line->update(['receipt_reference'=>'shirt-bank','reconciled_at'=>now()]);$this->assertSame(0,$p->fresh()->report()['funding_gap_cents']);
  $this->invalid(fn()=>$p->update(['delivery_cost_line_id'=>$p->setup_cost_line_id]));
 }
 public function test_admin_forms_actions_and_public_pages_keep_private_sizes_and_prospects_private(): void {
  $pack=$this->seedPlan();$this->get(\App\Filament\Resources\WelcomePackPlanResource::getUrl())->assertOk();
  foreach(['WelcomePackPlan','CommunityAward','Sponsorship'] as $name){$resource='App\\Filament\\Resources\\'.$name.'Resource';\Livewire\Livewire::test($resource.'\\Pages\\ManageRecords')->mountAction('create')->assertHasNoActionErrors();$record=$name==='WelcomePackPlan'?$pack:($name==='CommunityAward'?CommunityAward::first():Sponsorship::where('scope','award')->first());\Livewire\Livewire::test($resource.'\\Pages\\ManageRecords')->mountAction(\Filament\Actions\Testing\TestAction::make('edit')->table($record))->assertHasNoActionErrors();}
  \Livewire\Livewire::test(\App\Filament\Resources\WelcomePackPlanResource\Pages\ManageRecords::class)->mountAction(\Filament\Actions\Testing\TestAction::make('calculate')->table($pack))->assertSee('130')->assertSuccessful();
  $p=$pack->eventProject;$p->update(['is_public'=>true]);$sp=CommunityAward::first()->prizeSponsor;$sp->update(['sponsor_name'=>'Unconfirmed private prospect']);$pack->update(['people'=>[['person_key'=>'private-1','name'=>'PRIVATE BENEFICIARY','category'=>'service','size'=>'XL','confirmed'=>true]]]);
  foreach(['fr','pt'] as $locale)$this->get(route('community.show',['project'=>$p->slug,'lang'=>$locale]))->assertOk()->assertSee('500')->assertDontSee('Unconfirmed private prospect')->assertDontSee('PRIVATE BENEFICIARY');
  $this->app->detectEnvironment(fn()=>'local');$this->get(route('community.preview',['project'=>$p->slug]))->assertOk()->assertSee('130');auth('admin')->logout();$this->get(\App\Filament\Resources\WelcomePackPlanResource::getUrl())->assertRedirect();$this->get(route('community.preview',['project'=>$p->slug]))->assertForbidden();
 }
}
