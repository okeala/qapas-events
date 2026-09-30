<?php
namespace Tests\Feature;
use App\Models\{Admin,EventProject,ActivityLocation,FurniturePlan,PressRelease,Interest};
use App\Domain\Planning\{GeoArea,GeographicPlan,LaunchSequence,RelayMobilization};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;
class HospitalityOperationsTest extends TestCase {
 use RefreshDatabase;
 private function project(string $slug='test'): EventProject{return EventProject::create(['name'=>'Test','slug'=>$slug,'is_public'=>true,'geo_is_public'=>true]);}
 private function admin(): Admin {$a=Admin::create(['name'=>'Admin','email'=>'admin@example.test','password'=>'a-long-testing-password']);$this->actingAs($a,'admin');return $a;}
 private function box(float $x,float $y,float $w=1,float $h=1): array {return ['type'=>'Polygon','coordinates'=>[[[$x,$y],[$x+$w,$y],[$x+$w,$y+$h],[$x,$y+$h],[$x,$y]]]];}
 public function test_current_seed_is_versioned_unpaid_and_preserves_edits(): void {
  $this->seed();$p=EventProject::first();$s=$p->scenarios()->where('template_key','launch-hospitality-v1')->firstOrFail();
  $this->assertSame('distributed',$s->shelter_model);$this->assertCount(12,$s->includedStands);$this->assertSame(0,$s->budgetLines->sum('paid_quantity'));$this->assertFalse($s->report()['launch_ready']);
  $terrain=$s->budgetLines()->where('name','like','Nettoyage mimosas%')->firstOrFail();$this->assertSame(100000,$terrain->unit_gross_cents);$this->assertNull($terrain->vat_basis_points);
  $this->assertFalse($s->budgetLines->contains('scope','fries'));$this->assertDatabaseCount('prospects',42);$this->assertDatabaseCount('catering_services',1);$this->assertDatabaseCount('press_releases',11);
  $f=FurniturePlan::firstOrFail();$this->assertSame(12,$f->quantity);$f->update(['quantity'=>14]);$s->update(['event_days'=>2]);$this->seed();$this->assertSame(14,$f->fresh()->quantity);$this->assertSame(2,$s->fresh()->event_days);$this->assertDatabaseCount('stands',12);
  $this->get('/events/os-jogos-do-agricultor')->assertOk()->assertSee('chef Magalhães')->assertDontSee('friterie');
  $this->withSession(['locale'=>'pt'])->get('/events/os-jogos-do-agricultor')->assertOk()->assertSee('sopa de couve');
 }
 public function test_new_admin_resources_render_and_reject_inactive_users(): void {
  $this->seed();$a=$this->admin();foreach(['FurniturePlan','PressRelease','Prospect','CateringService'] as $name){$class='App\\Filament\\Resources\\'.$name.'Resource';$this->get($class::getUrl())->assertOk();}$a->update(['is_active'=>false]);$this->get(\App\Filament\Resources\ProspectResource::getUrl())->assertForbidden();
 }
 public function test_furniture_uses_six_seat_units_full_cans_labour_and_unknowns(): void {
  $p=$this->project();$f=FurniturePlan::create(['event_project_id'=>$p->id,'name'=>'Douglas','parts'=>FurniturePlan::illustrativeParts()]);$r=$f->report();$this->assertFalse($r['complete']);$this->assertNull($r['full_unit_cents']);$this->assertNull($r['rentals'][0]['rotations']);
  $f->update(['parts'=>[['name'=>'Test board','quantity'=>1,'length_mm'=>1000,'width_mm'=>100,'thickness_mm'=>10]],'quantity'=>2,'waste_basis_points'=>0,'wood_price_cents_m3'=>100000,'coverage_m2_litre'=>1,'coats'=>1,'hardware_cents_unit'=>200,'roller_cents_batch'=>300,'other_cents_batch'=>0,'work_minutes_unit'=>30,'hourly_cents'=>1000,'service_cents_unit'=>1000,'vat_basis_points'=>0]);
  $r=$f->report();$this->assertSame(1,$r['cans_batch']);$this->assertSame(4900,$r['cash_batch_cents']);$this->assertSame(2950,$r['full_unit_cents']);$this->assertSame(2000,$r['rentals'][0]['contribution_cents']);$this->assertSame(2,$r['rentals'][0]['rotations']);$this->assertFalse($r['verified']);
  $f->update(['inputs_verified'=>true,'evidence'=>'Supplier quotes and time study']);$this->assertTrue($f->report()['verified']);$f->update(['wood_price_cents_m3'=>200000]);$this->assertFalse($f->inputs_verified);
 }
 public function test_half_seated_hospitality_requires_more_than_one_included_six_seat_set(): void {
  $p=$this->project();$s=$p->scenarios()->create(['name'=>'Distributed','launch_model'=>true,'shelter_model'=>'distributed']);$stand=$p->stands()->create(['name'=>'Stand','kind'=>'independent','shelter_target'=>24,'sheltered_capacity'=>24,'seated_capacity'=>6,'shelter_source'=>'own','hospitality_validated'=>true,'hospitality_evidence'=>'Plan checked']);$s->includedStands()->attach($stand);
  $this->assertStringContainsString('moitié',implode(' ',$s->fresh()->report()['missing']));$stand->update(['seated_capacity'=>12]);$this->assertFalse($stand->hospitality_validated);$stand->update(['hospitality_validated'=>true]);$this->assertStringNotContainsString('moitié',implode(' ',$s->fresh()->report()['missing']));
 }
 public function test_soup_receipts_do_not_unlock_prepaid_launch(): void {
  $this->admin();$s=$this->project()->scenarios()->create(['name'=>'Test','launch_model'=>true,'organizer_full_monthly_cents'=>160000]);$l=$s->budgetLines()->create(['name'=>'Soup','kind'=>'revenue','scope'=>'soup','unit_gross_cents'=>1000000,'vat_basis_points'=>0,'forecast_quantity'=>1,'committed_quantity'=>1,'paid_quantity'=>1]);$l->update(['receipt_reference'=>'SOUP','reconciled_at'=>now()]);$this->assertSame(0,$s->fresh()->report()['verified_net_cents']);$this->assertFalse($s->fresh()->report()['launch_ready']);
 }
 public function test_interest_records_optional_quote_requests_without_payment_or_reservation(): void {
  $p=$this->project();config(['events.privacy_ready'=>true,'events.organizer_name'=>'QAPAS','events.contact_email'=>'info@example.test']);$data=['profile'=>'exhibitor','name'=>'Ana','email'=>'ana@example.test','privacy'=>1,'larger_tent_requested'=>1,'extra_furniture_requested'=>1,'expected_guests'=>36];
  $this->post('/events/test/interest',$data)->assertRedirect();$lead=Interest::firstOrFail();$this->assertTrue($lead->larger_tent_requested);$this->assertTrue($lead->extra_furniture_requested);$this->assertSame(36,$lead->expected_guests);$this->assertSame('new',$lead->status);$this->assertFalse($lead->marketing_opt_in);
  $this->post('/events/test/interest',array_replace($data,['expected_guests'=>-2]))->assertSessionHasErrors('expected_guests');
 }
 public function test_six_distinct_freguesias_unlock_concepts_but_not_private_locations(): void {
  $p=$this->project();$s=$p->scenarios()->create(['name'=>'Base','template_key'=>'launch-hospitality-v1']);$a=$p->activities()->create(['name'=>'Hidden official','rules'=>'REVEALED-RULES','track'=>'official','relay_reveal'=>true,'is_public'=>true,'publication_level'=>'hidden']);
  $f=$p->siteFeatures()->create(['name'=>'Private quartel','category'=>'quartel','geometry'=>$this->box(0,0)]);ActivityLocation::create(['activity_id'=>$a->id,'site_feature_id'=>$f->id,'name'=>'PRIVATE-FOOTPRINT','geometry'=>$this->box(.1,.1,.1,.1),'is_public'=>true]);
  $url=route('activity.show',['project'=>$p->slug,'activity'=>$a->public_id]);$this->get($url)->assertNotFound();
  for($i=1;$i<=6;$i++){$stand=$p->stands()->create(['name'=>'Stand '.$i,'kind'=>'village','freguesia'=>'Village '.$i]);$s->includedStands()->attach($stand);$stand->partners()->create(['name'=>'Café','relay_slot'=>1,'status'=>'active','mission'=>'Display and meeting','evidence'=>'Written agreement']);if($i===5)$this->assertFalse(app(RelayMobilization::class)->report($p)['ready']);}
  $this->assertTrue(app(RelayMobilization::class)->report($p)['ready']);$this->get($url)->assertOk()->assertSee('REVEALED-RULES')->assertDontSee('PRIVATE-FOOTPRINT');$this->assertStringNotContainsString('PRIVATE-FOOTPRINT',json_encode(app(GeographicPlan::class)->data($p)));
  $stand->update(['freguesia'=>'Village 1']);$this->assertFalse(app(RelayMobilization::class)->report($p)['ready']);$this->get($url)->assertNotFound();
 }
 public function test_launch_reordering_is_authorized_complete_and_topological(): void {
  $this->admin();$p=$this->project();$a=$p->ideas()->create(['name'=>'First','hypothesis'=>'Test','status'=>'idea']);$b=$p->ideas()->create(['name'=>'Next','hypothesis'=>'Test','status'=>'idea','depends_on'=>[$a->id]]);
  $page=Livewire::test(\App\Filament\Resources\IdeaResource\Pages\ManageRecords::class);$page->call('reorderTable',[(string)$a->id,(string)$b->id])->assertHasNoErrors();$this->assertLessThan($b->fresh()->sort_order,$a->fresh()->sort_order);
  $this->expectException(ValidationException::class);app(LaunchSequence::class)->validateOrder([$b->id,$a->id]);
 }
 public function test_press_drafts_are_private_and_published_content_is_escaped_and_edits_revoke_approval(): void {
  $p=$this->project();$press=PressRelease::create(['event_project_id'=>$p->id,'name'=>'Mobilization','body'=>'<script>unsafe()</script>','owner'=>'Editor','evidence'=>'Checked','is_public'=>true]);$url=route('press.show',['project'=>$p->slug,'press'=>$press->public_id]);$this->get($url)->assertNotFound();
  $press->update(['status'=>'published','published_at'=>now()]);$this->get($url)->assertOk()->assertSee('&lt;script&gt;',false)->assertDontSee('<script>unsafe()',false);$press->update(['body'=>'New unreviewed claim']);$this->assertSame('review',$press->status);$this->get($url)->assertNotFound();
  $this->expectException(ValidationException::class);$press->update(['kind'=>'revelation','status'=>'published','published_at'=>now()]);
 }
 public function test_footprints_can_cross_adjacent_quartels_but_not_holes_or_gaps(): void {
  $p=$this->project();$f=$p->siteFeatures()->create(['name'=>'A','category'=>'quartel','geometry'=>$this->box(0,0)]);$g=$p->siteFeatures()->create(['name'=>'B','category'=>'quartel','geometry'=>$this->box(1,0)]);$a=$p->activities()->create(['name'=>'A','rules'=>'Test']);$b=$p->activities()->create(['name'=>'B','rules'=>'Test']);
  $l=ActivityLocation::create(['activity_id'=>$a->id,'site_feature_id'=>$f->id,'additional_quartel_ids'=>[$g->id],'geometry'=>$this->box(.5,.1,1,.5)]);$other=ActivityLocation::create(['activity_id'=>$b->id,'site_feature_id'=>$f->id,'geometry'=>$this->box(.2,.2,.5,.5)]);$this->assertNotEmpty($l->overlaps());
  $hole=$this->box(0,0,3,3);$hole['coordinates'][]=$this->box(1,1,1,1)['coordinates'][0];$this->assertFalse(GeoArea::coveredBy($this->box(.5,.5,2,2),[$hole]));$this->assertFalse(GeoArea::coveredBy($this->box(.5,.1,2,.5),[$this->box(0,0),$this->box(2,0)]));
  $this->expectException(ValidationException::class);$g->update(['geometry'=>$this->box(3,0)]);
 }
 public function test_footprint_editor_scopes_activity_and_quartels_to_edition(): void {
  $this->admin();$p=$this->project();$q=$this->project('other');$a=$p->activities()->create(['name'=>'Game','rules'=>'Test']);$f=$p->siteFeatures()->create(['name'=>'A','category'=>'quartel','geometry'=>$this->box(0,0)]);$foreign=$q->siteFeatures()->create(['name'=>'Foreign','category'=>'quartel','geometry'=>$this->box(0,0)]);
  $editor=Livewire::test(\App\Filament\Pages\GeographicSite::class)->set('projectId',$p->public_id);$editor->call('savePlacement','Stage',$a->public_id,$f->public_id,[],$this->box(.1,.1,.3,.3),'performance','restricted',false)->assertHasNoErrors();$this->assertDatabaseCount('activity_locations',1);
  $editor->call('savePlacement','Intrusion',$a->public_id,$foreign->public_id,[],$this->box(.1,.1,.3,.3),'performance','restricted',false)->assertNotFound();
 }
}
