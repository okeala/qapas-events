<?php
namespace Tests\Feature;
use App\Models\{Admin,Activity,EventProject,Stand,StandPartner,SiteFeature,ActivityLocation,Idea};
use App\Domain\Planning\{GeoImport,GeographicPlan};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;
class LaunchAndGeographyTest extends TestCase {
 use RefreshDatabase;
 private function project(string $slug='test'): EventProject{return EventProject::create(['name'=>'Test','slug'=>$slug,'is_public'=>true]);}
 private function admin(): Admin {return Admin::create(['name'=>'Admin','email'=>'admin@example.test','password'=>'long-password-for-tests']);}
 private function geometry(): array {return ['type'=>'Polygon','coordinates'=>[[[-7.4,40.3],[-7.39,40.3],[-7.39,40.31],[-7.4,40.31],[-7.4,40.3]]]];}
 private function feature(EventProject $p,array $extra=[]): SiteFeature {return $p->siteFeatures()->create($extra+['name'=>'Quartel','category'=>'quartel','geometry'=>$this->geometry()]);}
 public function test_new_seed_creates_twelve_unassigned_stands_without_inventing_revenue_and_keeps_edits(): void {
  $this->seed();$p=EventProject::firstOrFail();$s=$p->scenarios()->where('template_key','launch-six-six')->firstOrFail();
  $this->assertSame(6,$s->team_target);$this->assertSame(6,$s->independent_target);$this->assertSame(12,$s->stand_target);$this->assertCount(12,$s->includedStands);$this->assertDatabaseCount('stand_partners',0);
  $this->assertFalse($s->report()['launch_ready']);$this->assertSame(0,$s->report()['verified_net_cents']);$this->assertSame(0,$s->budgetLines->sum('paid_quantity'));
  $stand=$s->includedStands->first();$stand->update(['specialty'=>'Notre recette']);$s->update(['event_days'=>2]);$this->seed();
  $this->assertSame('Notre recette',$stand->fresh()->specialty);$this->assertSame(2,$s->fresh()->event_days);$this->assertDatabaseCount('stands',12);$this->assertSame(1,$p->scenarios()->where('template_key','launch-six-six')->count());
 }
 public function test_independent_contribution_is_net_price_less_qapas_direct_costs_and_not_exhibitor_sales(): void {
  $p=$this->project();$stand=$p->stands()->create(['name'=>'Independent','kind'=>'independent','direct_costs_complete'=>true]);$s=$p->scenarios()->create(['name'=>'Budget','organizer_full_monthly_cents'=>160000,'costs_complete'=>true]);$s->includedStands()->attach($stand);
  $sale=$s->budgetLines()->create(['name'=>'QAPAS service','kind'=>'revenue','scope'=>'stand','stand_id'=>$stand->id,'unit_gross_cents'=>50000,'vat_basis_points'=>2300,'forecast_quantity'=>1,'committed_quantity'=>1,'paid_quantity'=>1]);
  $s->budgetLines()->create(['name'=>'Structure supplied','kind'=>'cost','scope'=>'stand','stand_id'=>$stand->id,'unit_gross_cents'=>12300,'vat_basis_points'=>2300,'deductible'=>true,'forecast_quantity'=>1]);
  $r=$s->fresh()->report();$this->assertSame(30650,$r['stands'][0]['forecast_cents']);$this->assertSame(-10000,$r['stands'][0]['verified_cents']);
  $this->actingAs($this->admin(),'admin');$sale->update(['receipt_reference'=>'BANK-ONE','reconciled_at'=>now()]);$r=$s->fresh()->report();$this->assertSame(30650,$r['stands'][0]['verified_cents']);
  $sale->update(['unit_gross_cents'=>40000]);$this->assertFalse($sale->fresh()->verified());
 }
 public function test_same_bank_receipt_cannot_be_counted_twice_within_a_scenario(): void {
  $this->actingAs($this->admin(),'admin');$s=$this->project()->scenarios()->create(['name'=>'Budget']);
  $data=['name'=>'Support','kind'=>'revenue','unit_gross_cents'=>10000,'vat_basis_points'=>0,'forecast_quantity'=>1,'committed_quantity'=>1,'paid_quantity'=>1];
  $first=$s->budgetLines()->create($data);$second=$s->budgetLines()->create(array_replace($data,['name'=>'Support from another partner']));$first->update(['receipt_reference'=>'ONE-TRANSFER','reconciled_at'=>now()]);
  $second->update(['receipt_reference'=>'TWO-TRANSFER','reconciled_at'=>now()]);
  $this->expectException(ValidationException::class);$second->update(['receipt_reference'=>'ONE-TRANSFER']);
 }
 public function test_bar_and_fries_do_not_unlock_prepaid_growth(): void {
  $this->actingAs($this->admin(),'admin');$s=$this->project()->scenarios()->create(['name'=>'Budget','launch_model'=>true,'organizer_full_monthly_cents'=>160000,'costs_complete'=>true]);
  foreach(['bar','fries'] as $scope){$line=$s->budgetLines()->create(['name'=>$scope,'kind'=>'revenue','scope'=>$scope,'unit_gross_cents'=>1000000,'vat_basis_points'=>0,'forecast_quantity'=>1,'committed_quantity'=>1,'paid_quantity'=>1]);$line->update(['receipt_reference'=>$scope,'reconciled_at'=>now()]);}
  $r=$s->fresh()->report();$this->assertSame(0,$r['verified_net_cents']);$this->assertFalse($r['expansion_ready']);$this->assertSame(-160000,$r['secured_margin_cents']);
 }
 public function test_personal_advance_repayment_is_not_a_second_expense(): void {
  $s=$this->project()->scenarios()->create(['name'=>'Budget','organizer_net_monthly_cents'=>0,'organizer_full_monthly_cents'=>0,'costs_complete'=>true]);
  $s->budgetLines()->create(['name'=>'Already paid by organizer','kind'=>'cost','unit_gross_cents'=>12300,'vat_basis_points'=>2300,'deductible'=>true,'forecast_quantity'=>1,'committed_quantity'=>1,'paid_quantity'=>1,'paid_by'=>'organizer','reimbursed_cents'=>3000]);
  $r=$s->report();$this->assertSame(-10000,$r['forecast_margin_cents']);$this->assertSame(9300,$r['advance_due_cents']);$this->assertSame(-12300,$r['cash_after_reserves_cents']);
 }
 public function test_one_principal_and_three_relay_slots_with_combined_roles(): void {
  $stand=$this->project()->stands()->create(['name'=>'Village','kind'=>'village']);
  foreach([1,2,3] as $slot)$stand->partners()->create(['name'=>'Partner '.$slot,'main_slot'=>$slot===1?1:null,'relay_slot'=>$slot]);
  $this->assertCount(3,$stand->partners);$this->expectException(ValidationException::class);$stand->partners()->create(['name'=>'Fourth','relay_slot'=>4]);
 }
 public function test_geojson_and_kml_import_preserve_coordinates_and_reject_unsafe_xml(): void {
  $geo=['type'=>'FeatureCollection','features'=>[['type'=>'Feature','properties'=>['name'=>'Quartel A'],'geometry'=>$this->geometry()]]];$service=app(GeoImport::class);$items=$service->parse(json_encode($geo),'geojson');$this->assertSame($this->geometry(),$items[0]['geometry']);
  $kml='<kml xmlns="http://www.opengis.net/kml/2.2"><Document><Placemark><name>Entrada</name><Point><coordinates>-7.4,40.3,610</coordinates></Point></Placemark></Document></kml>';
  $items=$service->parse($kml,'kml');$this->assertSame([-7.4,40.3],$items[0]['geometry']['coordinates']);
  $this->expectException(ValidationException::class);$service->parse('<!DOCTYPE kml [<!ENTITY x SYSTEM "file:///etc/passwd">]><kml>&x;</kml>','kml');
 }
 public function test_geo_public_projection_hides_private_objects_needs_and_unrevealed_activities(): void {
  $p=$this->project();$f=$this->feature($p,['is_public'=>true,'notes'=>'PRIVATE-NOTE']);$this->feature($p,['name'=>'PRIVATE-FEATURE']);$f->needs()->create(['name'=>'PRIVATE-COST']);
  $a=$p->activities()->create(['name'=>'SECRET-GAME','rules'=>'SECRET-RULE','publication_level'=>'hidden','is_public'=>true]);ActivityLocation::create(['activity_id'=>$a->id,'site_feature_id'=>$f->id,'role'=>'performance']);
  $this->assertNull(app(GeographicPlan::class)->data($p));$p->update(['geo_is_public'=>true]);$data=app(GeographicPlan::class)->data($p);$this->assertCount(1,$data['geojson']['features']);
  $text=json_encode($data);foreach(['PRIVATE-NOTE','PRIVATE-FEATURE','PRIVATE-COST','SECRET-GAME','SECRET-RULE'] as $secret)$this->assertStringNotContainsString($secret,$text);
  $this->get('/events/test')->assertOk()->assertSee('data-geo-canvas',false)->assertDontSee('SECRET-GAME');
 }
 public function test_real_geographic_editor_imports_only_after_preview_and_rejects_foreign_records(): void {
  $p=$this->project();$other=$this->project('other');$foreign=$this->feature($other);$admin=$this->admin();$this->actingAs($admin,'admin');
  $json=json_encode(['type'=>'Feature','properties'=>['name'=>'Imported'],'geometry'=>$this->geometry()]);
  $editor=Livewire::test(\App\Filament\Pages\GeographicSite::class)->set('projectId',$p->public_id)->set('importFile',UploadedFile::fake()->createWithContent('plan.geojson',$json))->call('previewImport')->assertHasNoErrors();$this->assertSame(0,$p->siteFeatures()->count());
  $editor->call('commitImport')->assertHasNoErrors();$this->assertSame(1,$p->siteFeatures()->count());
  $editor->call('saveFeature','Intrusion','quartel',$this->geometry(),$foreign->public_id)->assertNotFound();
 }
 public function test_geographic_editor_denies_inactive_admin_mutations(): void {
  $p=$this->project();$admin=$this->admin();$this->actingAs($admin,'admin');$editor=Livewire::test(\App\Filament\Pages\GeographicSite::class);$admin->update(['is_active'=>false]);
  $editor->call('saveFeature','No','quartel',$this->geometry())->assertForbidden();$this->assertDatabaseCount('site_features',0);
 }
 public function test_multiple_activity_zones_share_one_activity_cost_and_require_same_edition(): void {
  $p=$this->project();$a=$p->activities()->create(['name'=>'Game','rules'=>'Test']);foreach(['A','B'] as $name){$f=$this->feature($p,['name'=>$name]);ActivityLocation::create(['activity_id'=>$a->id,'site_feature_id'=>$f->id]);}$this->assertCount(2,$a->locations);
  $foreign=$this->feature($this->project('other'));$this->expectException(ValidationException::class);ActivityLocation::create(['activity_id'=>$a->id,'site_feature_id'=>$foreign->id]);
 }
 public function test_launch_steps_need_evidence_and_valid_dependencies_and_reject_cycles(): void {
  $p=$this->project();$a=$p->ideas()->create(['name'=>'First','hypothesis'=>'Test','status'=>'idea']);$b=$p->ideas()->create(['name'=>'Next','hypothesis'=>'Test','status'=>'idea','depends_on'=>[$a->id]]);
  try{$b->update(['status'=>'validated','owner'=>'Person','evidence'=>'Evidence']);$this->fail('Dependency ignored');}catch(ValidationException){$this->assertFalse($b->fresh()->isValidated());}
  $a->update(['status'=>'validated','owner'=>'Person','evidence'=>'Evidence']);$b->fresh()->update(['status'=>'validated','owner'=>'Person','evidence'=>'Evidence']);$this->assertTrue($b->fresh()->isValidated());
  $this->expectException(ValidationException::class);$a->update(['depends_on'=>[$b->id]]);
 }
 public function test_teaser_does_not_leak_rules_and_confirmation_requires_real_preparation(): void {
  $p=$this->project();$a=$p->activities()->create(['name'=>'Teaser','summary'=>'Visible idea','rules'=>'SECRET-RULE','scoring'=>'SECRET-SCORE','publication_level'=>'teaser','is_public'=>true]);
  $this->get(route('activity.show',['project'=>$p->slug,'activity'=>$a->public_id]))->assertOk()->assertSee('Visible idea')->assertDontSee('SECRET-RULE')->assertDontSee('SECRET-SCORE');
  $this->expectException(ValidationException::class);$a->update(['publication_level'=>'confirmed']);
 }
 public function test_site_costs_are_included_once_and_scaled_by_days(): void {
  $p=$this->project();$f=$this->feature($p,['name'=>'Tent','category'=>'tent','needs_complete'=>true]);$f->needs()->create(['name'=>'Rental','quantity'=>1,'basis'=>'per_day','unit_gross_cents'=>12300,'vat_basis_points'=>2300,'deductible'=>true]);
  $s=$p->scenarios()->create(['name'=>'Budget','event_days'=>2,'organizer_net_monthly_cents'=>0,'organizer_full_monthly_cents'=>0,'costs_complete'=>true]);$s->includedFeatures()->sync([$f->id]);$s->includedFeatures()->sync([$f->id]);$r=$s->fresh()->report();
  $this->assertSame(20000,$r['site_cost_cents']);$this->assertSame(-20000,$r['forecast_margin_cents']);$this->assertSame(-24600,$r['prepaid_cash_cents']);$this->assertTrue($r['complete']);
 }
 public function test_parent_cycles_are_rejected_and_growth_cannot_bypass_parent_funding(): void {
  $p=$this->project();$a=$p->scenarios()->create(['name'=>'Base']);$b=$p->scenarios()->create(['name'=>'Larger','parent_id'=>$a->id]);$this->assertFalse($b->report()['expansion_ready']);
  $this->expectException(ValidationException::class);$a->update(['parent_id'=>$b->id]);
 }
 public function test_twelve_stand_format_unlocks_only_with_verified_funding_complete_program_and_preserved_surplus(): void {
  $this->actingAs($this->admin(),'admin');$p=$this->project();
  $s=$p->scenarios()->create(['name'=>'6 + 6','launch_model'=>true,'team_target'=>6,'independent_target'=>6,'stand_target'=>12,'event_days'=>1,'guests_per_stand'=>20,'tent_capacity'=>240,'organizer_full_monthly_cents'=>160000,'costs_complete'=>true,'contingency_cents'=>10000,'refund_reserve_cents'=>10000,'reserve_evidence'=>'Scenario exposure checked']);
  $sales=[];
  for($i=1;$i<=12;$i++){
   $stand=$p->stands()->create(['name'=>'Stand '.$i,'kind'=>$i<=6?'village':'independent','direct_costs_complete'=>true]);$s->includedStands()->attach($stand);
   if($i<=6)$stand->partners()->create(['name'=>'Sponsor '.$i,'main_slot'=>1,'status'=>'active','mission'=>'Finance the stand','evidence'=>'Signed agreement']);
   $s->budgetLines()->create(['name'=>'Direct cost '.$i,'kind'=>'cost','scope'=>'stand','stand_id'=>$stand->id,'unit_gross_cents'=>10000,'vat_basis_points'=>0,'forecast_quantity'=>1]);
   $sales[]=$s->budgetLines()->create(['name'=>'Service '.$i,'kind'=>'revenue','scope'=>'stand','stand_id'=>$stand->id,'unit_gross_cents'=>70000,'vat_basis_points'=>0,'forecast_quantity'=>1,'committed_quantity'=>1,'paid_quantity'=>1]);
  }
  $s->budgetLines()->create(['name'=>'Common costs','kind'=>'cost','unit_gross_cents'=>100000,'vat_basis_points'=>0,'forecast_quantity'=>1]);
  for($i=1;$i<=3;$i++){
   $a=$p->activities()->create(['name'=>'Official '.$i,'track'=>'official','rules'=>'Test','materials_complete'=>true]);
   $a->materials()->create(['name'=>'Equipment','quantity'=>1,'unit_gross_cents'=>10000,'vat_basis_points'=>0]);
   $s->includedActivities()->attach($a);$s->programSlots()->create(['activity_id'=>$a->id,'day_number'=>1]);
  }
  $this->assertFalse($s->fresh()->report()['launch_ready']);
  foreach($sales as $i=>$sale)$sale->update(['receipt_reference'=>'PAYMENT-'.$i,'reconciled_at'=>now()]);
  $r=$s->fresh()->report();$this->assertTrue($r['complete'],implode('; ',$r['missing']));$this->assertSame(420000,$r['prepaid_margin_cents']);$this->assertSame(410000,$r['prepaid_cash_cents']);$this->assertTrue($r['launch_ready']);$this->assertTrue($r['expansion_ready']);
  $sales[0]->update(['paid_quantity'=>0]);$r=$s->fresh()->report();$this->assertTrue($r['launch_ready']);$this->assertFalse($r['expansion_ready']);
  $s->programSlots()->first()->delete();$r=$s->fresh()->report();$this->assertFalse($r['complete']);$this->assertFalse($r['launch_ready']);
 }

}
