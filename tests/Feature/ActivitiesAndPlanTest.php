<?php
namespace Tests\Feature;
use App\Models\{Activity,Admin,EventProject,Terrace,User};
use App\Domain\Planning\{SitePlan,TerraceGeometry,Readiness};
use Database\Seeders\OfficialActivitiesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;
class ActivitiesAndPlanTest extends TestCase {
 use RefreshDatabase;
 private function project(string $slug='test'): EventProject {return EventProject::create(['name'=>'Test','slug'=>$slug,'is_public'=>true]);}
 private function admin(): Admin {return Admin::create(['name'=>'Admin','email'=>'admin@example.test','password'=>'long-password-for-testing']);}
 private function terrace(EventProject $p,array $extra=[]): Terrace {return $p->terraces()->create($extra+['name'=>'Terrasse','boundary'=>[[0,0],[40,0],[40,40],[0,40]],'access'=>'restricted','is_public'=>false]);}
 private function image(EventProject $p): void {Storage::fake('local');$p->update(['plan_image'=>app(SitePlan::class)->upload(UploadedFile::fake()->image('plan.jpg',400,200))]);}
 public function test_upgrade_keeps_existing_work_and_reseeding_does_not_overwrite_edits(): void {
  $p=$this->project('forqua-de-ouro');$p->update(['name'=>'Forqua de Ouro']);$id=$p->id;
  $custom=$p->activities()->create(['name'=>'Notre jeu','rules'=>'Notre création','status'=>'testing']);
  $scenario=$p->scenarios()->create(['name'=>'Budget existant']);
  $this->seed();$this->assertSame($id,EventProject::first()->id);$this->assertSame('os-jogos-do-agricultor',$p->fresh()->slug);
  $this->assertSame('Os Jogos do Agricultor',$p->fresh()->name);$this->assertSame(6,$p->activities()->whereNotNull('template_key')->count());
  $a=$p->activities()->whereNotNull('template_key')->first();$a->update(['rules'=>'Règles corrigées']);$a->materials()->first()->update(['quantity'=>12]);$count=$a->materials()->count();
  $this->seed();$this->assertSame('Règles corrigées',$a->fresh()->rules);$this->assertSame(12,$a->materials()->first()->quantity);$this->assertSame($count,$a->materials()->count());
  $this->assertDatabaseHas('activities',['id'=>$custom->id,'rules'=>'Notre création']);$this->assertDatabaseHas('scenarios',['id'=>$scenario->id,'name'=>'Budget existant']);
 }
 public function test_public_cards_use_an_allowlist_and_require_both_public_flags(): void {
  $p=$this->project();$a=$p->activities()->create(['name'=>'Visible','summary'=>'Résumé','rules'=>'Règles publiques','is_public'=>true,'original_concept'=>'ORIGINAL_SECRET','technical_review'=>'TECH_SECRET','risk_evidence'=>'RISK_SECRET']);
  $a->materials()->create(['name'=>'PRICE_SECRET','quantity'=>1,'unit_gross_cents'=>12345]);
  $url=route('activity.show',['project'=>$p->slug,'activity'=>$a->public_id]);
  $this->get($url)->assertOk()->assertSee('Règles publiques')->assertDontSee('ORIGINAL_SECRET')->assertDontSee('TECH_SECRET')->assertDontSee('RISK_SECRET')->assertDontSee('PRICE_SECRET');
  $a->update(['is_public'=>false]);$this->get($url)->assertNotFound();$this->get('/events/test')->assertDontSee('Visible');
  $a->update(['is_public'=>true]);$p->update(['is_public'=>false]);$this->get($url)->assertNotFound();
  $other=$this->project('other');$this->get(route('activity.show',['project'=>$other->slug,'activity'=>$a->public_id]))->assertNotFound();
 }
 public function test_materials_scale_per_run_and_flow_into_selected_scenario_once(): void {
  $p=$this->project();$a=$p->activities()->create(['name'=>'Eggs','rules'=>'Test','planned_runs'=>3,'materials_complete'=>true]);
  $a->materials()->create(['name'=>'Fixed','quantity'=>1,'unit_gross_cents'=>12300,'vat_basis_points'=>2300,'deductible'=>true]);
  $a->materials()->create(['name'=>'Per run','quantity'=>6,'basis'=>'per_run','unit_gross_cents'=>123,'vat_basis_points'=>2300,'deductible'=>true]);
  $cost=$a->costReport();$this->assertSame(14514,$cost['gross_cents']);$this->assertSame(11800,$cost['economic_cents']);$this->assertTrue($cost['complete']);
  $s=$p->scenarios()->create(['name'=>'Selected','costs_complete'=>true,'organizer_full_monthly_cents'=>160000]);$s->includedActivities()->sync([$a->id]);$s->includedActivities()->sync([$a->id]);
  $r=$s->fresh()->report();$this->assertSame(11800,$r['activity_cost_cents']);$this->assertSame(-171800,$r['secured_margin_cents']);$this->assertSame(-174514,$r['cash_after_reserves_cents']);$this->assertTrue($r['complete']);
  $other=$p->scenarios()->create(['name'=>'Without']);$this->assertSame(0,$other->report()['activity_cost_cents']);
 }
 public function test_unknown_materials_and_unconfirmed_free_loan_keep_financials_incomplete(): void {
  $a=$this->project()->activities()->create(['name'=>'Test','rules'=>'Test','materials_complete'=>true]);
  $m=$a->materials()->create(['name'=>'Loan','quantity'=>1,'procurement'=>'loan']);$this->assertFalse($a->fresh()->costReport()['complete']);
  $m->update(['unit_gross_cents'=>0,'vat_basis_points'=>0]);$this->assertFalse($a->fresh()->costReport()['complete']);
  $m->update(['evidence'=>'Written loan agreement']);$this->assertTrue($a->fresh()->costReport()['complete']);
 }
 public function test_geometry_rejects_crossing_and_out_of_range_polygons(): void {
  foreach([[[0,0],[40,40],[40,0],[0,40]],[[0,0],[101,0],[0,10]],[[0,0],[10,10],[20,20]]] as $points){
   try{TerraceGeometry::validate($points);$this->fail('Invalid polygon accepted');}catch(ValidationException $e){$this->assertArrayHasKey('boundary',$e->errors());}
  }
  $this->assertTrue(TerraceGeometry::contains([[0,0],[40,0],[40,40],[0,40]],0,20));
 }
 public function test_placement_rejects_another_edition_and_points_outside_terrace(): void {
  $p=$this->project();$t=$this->terrace($p);$a=$p->activities()->create(['name'=>'Test','rules'=>'Test']);
  $foreign=$this->terrace($this->project('other'));
  foreach([['terrace_id'=>$foreign->id],['terrace_id'=>$t->id,'map_x'=>90,'map_y'=>90]] as $data){
   try{$a->fresh()->update($data);$this->fail('Invalid placement accepted');}catch(ValidationException $e){$this->assertNotEmpty($e->errors());}
  }
  $a->update(['terrace_id'=>$t->id,'map_x'=>20,'map_y'=>20]);$a->update(['status'=>'approved','risk_reviewed'=>true]);
  $a->update(['map_x'=>21]);$this->assertFalse($a->fresh()->risk_reviewed);$this->assertSame('testing',$a->fresh()->status);
  $this->expectException(ValidationException::class);$t->update(['boundary'=>[[0,0],[10,0],[10,10],[0,10]]]);
 }
 public function test_plan_image_and_markers_stay_private_until_explicit_publication(): void {
  $p=$this->project();$this->image($p);$t=$this->terrace($p,['is_public'=>true]);$hidden=$this->terrace($p,['name'=>'Hidden terrace']);
  $p->activities()->create(['name'=>'Public game','rules'=>'Test','is_public'=>true,'terrace_id'=>$t->id,'map_x'=>20,'map_y'=>20]);
  $p->activities()->create(['name'=>'Secret game','rules'=>'Test','is_public'=>false,'terrace_id'=>$t->id,'map_x'=>10,'map_y'=>10]);
  $p->activities()->create(['name'=>'Hidden placement','rules'=>'Test','is_public'=>true,'terrace_id'=>$hidden->id,'map_x'=>10,'map_y'=>10]);
  $url=route('plan.image',['project'=>$p->public_id]);$private=route('plan.private-image',['project'=>$p->public_id]);
  $this->get($url)->assertNotFound();$this->get($private)->assertForbidden();$this->assertNull(app(SitePlan::class)->data($p));
  $p->update(['plan_is_public'=>true]);$this->get($url)->assertOk()->assertHeader('Content-Type','image/png');$data=app(SitePlan::class)->data($p);
  $this->get('/events/test')->assertOk()->assertSee('data-plan-canvas',false)->assertDontSee('Hidden terrace')->assertDontSee('Secret game');
  $this->assertCount(1,$data['terraces']);$this->assertCount(1,$data['activities']);$this->assertSame('Public game',$data['activities'][0]['name']);
  $this->actingAs($this->admin(),'admin')->get($private)->assertOk();$p->update(['is_public'=>false]);$this->get($url)->assertNotFound();
 }
 public function test_real_editor_uploads_draws_and_places_with_server_validation(): void {
  $p=$this->project();Storage::fake('local');$this->actingAs($this->admin(),'admin');
  $a=$p->activities()->create(['name'=>'Test','rules'=>'Test']);
  $editor=Livewire::test(\App\Filament\Pages\SitePlan::class)->set('projectId',$p->public_id)->set('planUpload',UploadedFile::fake()->image('plan.jpg',400,200))->call('uploadPlan')->assertHasNoErrors();
  $this->assertNotNull($p->fresh()->plan_image);$this->assertFalse($p->fresh()->plan_is_public);
  $editor->call('saveTerrace','Terrasse',[[0,0],[40,0],[40,40],[0,40]])->assertHasNoErrors();$t=$p->terraces()->firstOrFail();
  $editor->call('placeActivity',$a->public_id,$t->public_id,20,20)->assertHasNoErrors();$this->assertSame(20.0,$a->fresh()->map_x);
  $editor->call('placeActivity',$a->public_id,$t->public_id,90,90)->assertHasErrors('map_x');$this->assertSame(20.0,$a->fresh()->map_x);
 }
 public function test_inactive_admin_cannot_mutate_editor_even_after_mount(): void {
  $p=$this->project();$this->image($p);$admin=$this->admin();$this->actingAs($admin,'admin');$editor=Livewire::test(\App\Filament\Pages\SitePlan::class);
  $admin->update(['is_active'=>false]);$editor->call('saveTerrace','Forbidden',[[0,0],[20,0],[20,20]])->assertForbidden();$this->assertDatabaseCount('terraces',0);
 }
 public function test_activity_form_saves_materials_and_scenario_form_links_them(): void {
  $p=$this->project();$this->actingAs($this->admin(),'admin');
  Livewire::test(\App\Filament\Resources\ActivityResource\Pages\ManageRecords::class)->callAction('create',data:[
   'event_project_id'=>$p->id,'name'=>'Stand comedy','rules'=>'A comic sequence','track'=>'public','proposer_type'=>'independent','status'=>'idea','capacity'=>3,'planned_runs'=>2,'risk_category'=>'manual','access'=>'everyone','sort_order'=>10,
   'materials'=>[['pricing_status'=>'estimate','name'=>'Prop','quantity'=>2,'unit'=>'pièce','basis'=>'per_run','procurement'=>'purchase','unit_gross_cents'=>100,'vat_basis_points'=>0]],
  ])->assertHasNoActionErrors();$a=Activity::where('name','Stand comedy')->firstOrFail();$this->assertCount(1,$a->materials);
  Livewire::test(\App\Filament\Resources\ScenarioResource\Pages\ManageRecords::class)->callAction('create',data:['event_project_id'=>$p->id,'name'=>'With games','minimum_organizer_charges_cents'=>80000,'organizer_cost_evidence'=>'Test edition: minimum charges included in full monthly cost.','months'=>1,'target_surplus_cents'=>400000,'organizer_net_monthly_cents'=>100000,'organizer_full_monthly_cents'=>160000,'team_target'=>1,'stand_target'=>2,'includedActivities'=>[$a->id]])->assertHasNoActionErrors();
  $this->assertSame(400,$p->scenarios()->firstOrFail()->report()['activity_cost_cents']);
 }
 public function test_machinery_cannot_pass_live_readiness_without_qualified_separate_areas(): void {
  $p=$this->project();$p->activities()->create(['name'=>'Unsafe tractor','rules'=>'Test','track'=>'official','status'=>'approved','risk_category'=>'machinery','risk_reviewed'=>true,'risk_evidence'=>'A checkbox alone','referee'=>'Name','capacity'=>1]);
  $this->assertStringContainsString('Unsafe tractor',implode(' ',app(Readiness::class)->blockers($p,'live')));
 }
}
