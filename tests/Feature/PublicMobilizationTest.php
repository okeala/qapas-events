<?php
namespace Tests\Feature;
use App\Models\{EventProject,FreguesiaInvitation,Team,Admin,CommercialPlan};
use App\Domain\Promotion\PublicMobilization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class PublicMobilizationTest extends TestCase {
 use RefreshDatabase;
 private function project(): EventProject {$this->seed();return EventProject::where('slug','os-jogos-do-agricultor')->firstOrFail();}
 private function invalid(callable $f): void {try{$f();$this->fail('Validation expected');}catch(ValidationException){$this->assertTrue(true);}}
 private function admin(): void {$this->actingAs(Admin::firstOrCreate(['email'=>'directory@example.test'],['name'=>'Admin','password'=>'long-test-password']),'admin');}
 public function test_cards_have_real_destinations_and_directory_does_not_invent_invitations(): void {
  $p=$this->project();$this->assertDatabaseCount('freguesia_invitations',6);$this->assertDatabaseCount('teams',0);$this->assertSame(6,FreguesiaInvitation::where('status','planned')->count());
  $page=$this->get(route('event.show',['project'=>$p->slug]))->assertOk()->assertSee('12 stands')->assertDontSee('Le format prend forme')->assertDontSee('La fête se prépare dans les villages')->assertDontSee('Ils nous soutiennent déjà');
  foreach(['communes','teams','relays','sponsors','growth'] as $part){$url=route('mobilization.'.$part,['project'=>$p->slug]);$page->assertSee($url,false);$this->get($url)->assertOk();}
  $v=FreguesiaInvitation::first();$this->get(route('mobilization.commune',['project'=>$p->slug,'commune'=>$v->public_id]))->assertOk()->assertSee($v->name);
  $this->invalid(fn()=>$v->update(['status'=>'invited']));$v->refresh();$v->update(['status'=>'invited','invited_at'=>now(),'invitation_evidence'=>'SECRET DELIVERY']);$this->get(route('mobilization.commune',['project'=>$p->slug,'commune'=>$v->public_id]))->assertDontSee('SECRET DELIVERY');$this->seed();$this->assertSame('invited',$v->fresh()->status);$this->assertDatabaseCount('freguesia_invitations',6);
  $this->withSession(['locale'=>'pt'])->get(route('event.show',['project'=>$p->slug]))->assertOk()->assertSee('Ver as freguesias')->assertDontSee('Le format prend forme');
 }
 public function test_local_demo_teams_are_persisted_idempotently_and_never_published_in_production(): void {
  $this->app->instance('env','local');$p=$this->project();$this->seed();$this->assertSame(6,Team::where('is_demo',true)->count());$team=Team::where('is_demo',true)->first();$this->assertCount(14,$team->roleAssignments);
  $url=route('mobilization.team',['project'=>$p->slug,'team'=>$team->public_id]);$this->get($url)->assertOk()->assertSee('équipe fictive');$this->invalid(fn()=>$team->update(['status'=>'elected']));$team->refresh();$this->invalid(fn()=>$team->roleAssignments()->first()->update(['candidate_name'=>'Actual person']));
  $this->app->instance('env','production');$this->get($url)->assertNotFound();$this->get(route('mobilization.teams',['project'=>$p->slug]))->assertOk()->assertDontSee($team->name);$team->refresh();$team->update(['is_public'=>true]);$this->get($url)->assertNotFound();
 }
 public function test_team_and_commune_pages_enforce_visibility_edition_and_private_fields(): void {
  $p=$this->project();$v=FreguesiaInvitation::first();$team=$p->teams()->create(['name'=>'Visible team','freguesia'=>$v->name,'freguesia_invitation_id'=>$v->id,'is_public'=>true,'representative'=>'SECRET REPRESENTATIVE','election_protocol'=>'SECRET PROTOCOL','summary_fr'=>'<script>PRIVATE HTML</script>']);
  $url=route('mobilization.team',['project'=>$p->slug,'team'=>$team->public_id]);$this->get($url)->assertOk()->assertDontSee('SECRET REPRESENTATIVE')->assertDontSee('SECRET PROTOCOL')->assertSee('&lt;script&gt;',false)->assertDontSee('<script>PRIVATE HTML',false);
  $other=EventProject::create(['name'=>'Other','slug'=>'other','is_public'=>true]);$this->get(route('mobilization.team',['project'=>$other->slug,'team'=>$team->public_id]))->assertNotFound();$this->get(route('mobilization.commune',['project'=>$other->slug,'commune'=>$v->public_id]))->assertNotFound();
  $team->update(['is_public'=>false]);$this->get($url)->assertNotFound();$v->update(['is_public'=>false]);$this->get(route('mobilization.commune',['project'=>$p->slug,'commune'=>$v->public_id]))->assertNotFound();$p->update(['is_public'=>false]);foreach(['communes','teams','relays','sponsors','growth'] as $part)$this->get(route('mobilization.'.$part,['project'=>$p->slug]))->assertNotFound();
 }
 public function test_relay_map_and_thanks_use_confirmed_published_rows_without_private_evidence(): void {
  $p=$this->project();$stand=$p->stands()->where('kind','village')->first();$stand->update(['is_public'=>true,'freguesia'=>'Test commune']);$partner=$stand->partners()->create(['name'=>'Café <test>','relay_slot'=>1,'main_slot'=>1,'status'=>'active','mission'=>'SECRET MISSION','evidence'=>'SECRET AGREEMENT','is_public'=>true,'public_address'=>'Public address','latitude'=>40.34,'longitude'=>-7.35,'location_evidence'=>'SECRET LOCATION']);
  $url=route('mobilization.relays',['project'=>$p->slug]);$this->get($url)->assertOk()->assertSee('Public address')->assertSee('data-relay-json',false)->assertDontSee('SECRET');$map=app(PublicMobilization::class)->map(app(PublicMobilization::class)->relays($p));$this->assertCount(1,$map);$this->assertEqualsCanonicalizing(['name','address','lat','lng','anchor'],array_keys($map[0]));
  $this->get(route('event.show',['project'=>$p->slug]))->assertSee('Ils nous soutiennent déjà');$this->get(route('mobilization.sponsors',['project'=>$p->slug]))->assertOk()->assertSee('Café &lt;test&gt;',false)->assertDontSee('SECRET AGREEMENT');
  $partner->update(['latitude'=>null,'longitude'=>null]);$this->get($url)->assertOk()->assertSee('Public address')->assertDontSee('data-relay-json',false);$partner->update(['is_public'=>false]);$this->get($url)->assertDontSee('Public address');$this->get(route('event.show',['project'=>$p->slug]))->assertDontSee('Ils nous soutiennent déjà');
 }
 public function test_growth_exposes_marginal_contribution_without_treating_all_other_costs_as_fixed(): void {
  $p=$this->project();$plan=CommercialPlan::first();$r=app(\App\Domain\Finance\StandEconomics::class)->report($plan);$this->assertSame(547824,$r['stand_direct_cents']);$this->assertSame(37739,$r['independent_direct_cents']);$this->assertSame(15107,$r['independent_contribution_cents']);$this->assertSame(60428,$r['extensions'][1]['contribution_cents']);$this->assertTrue($r['incomplete']);
  $this->get(route('mobilization.growth',['project'=>$p->slug]))->assertOk()->assertSee('sanitaires')->assertSee('profile=exhibitor',false)->assertDontSee('4 000');
  $this->admin();$this->get(\App\Filament\Resources\FreguesiaInvitationResource::getUrl())->assertOk();\Livewire\Livewire::test(\App\Filament\Resources\FreguesiaInvitationResource\Pages\ManageRecords::class)->callAction('create',data:['event_project_id'=>$p->id,'source_key'=>'new-test','name'=>'Additional commune','municipality'=>'Guarda','status'=>'planned','is_public'=>false])->assertHasNoActionErrors();
  $page=\Livewire\Livewire::test(\App\Filament\Resources\CommercialPlanResource\Pages\ManageRecords::class)->mountAction(\Filament\Actions\Testing\TestAction::make('report')->table($plan))->assertActionMounted(\Filament\Actions\Testing\TestAction::make('report')->table($plan));$this->assertStringContainsString('Stands de freguesia, indépendants et sponsors',$page->instance()->getMountedAction()->getModalContent()->render());
 }
}
