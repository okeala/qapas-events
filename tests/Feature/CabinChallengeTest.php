<?php
namespace Tests\Feature;

use App\Domain\Planning\Readiness;
use App\Filament\Resources\CabinProjectResource;
use App\Filament\Resources\CabinProjectResource\Pages\ManageRecords;
use App\Models\{Admin, CabinProject, EventProject, Sponsorship};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CabinChallengeTest extends TestCase
{
    use RefreshDatabase;

    private function project(string $slug='test'): EventProject
    {
        return EventProject::create(['name'=>'Jeux','slug'=>$slug,'is_public'=>true,'cabin_policy_version'=>'reclaimed-v1']);
    }
    private function admin(): Admin
    {
        $admin = Admin::create(['name'=>'Cabins','email'=>'cabins@example.test','password'=>'long-password-for-tests']);
        $this->actingAs($admin,'admin');
        return $admin;
    }
    private function cabin(EventProject $project, bool $rental=false): CabinProject
    {
        $stand = $project->stands()->create(['name'=>'Stand '.($rental?'independent':'village'),'kind'=>$rental?'independent':'village','is_public'=>true]);
        return CabinProject::create(['event_project_id'=>$project->id,'stand_id'=>$stand->id,'name'=>'La cabane','supply_mode'=>$rental?'qapas_rental':'team_build']);
    }
    private function invalid(callable $action): void
    {
        try { $action(); $this->fail('Expected server validation'); }
        catch (ValidationException) { $this->assertTrue(true); }
    }
    private function inventory(): array
    {
        return array_map(fn($part,$material,$source)=>['name'=>$part,'part'=>$part,'material'=>$material,'source'=>$source,'quantity'=>1,'unit'=>'lot','origin'=>'PRIVATE-STOCK specification and provenance','max_diameter_mm'=>in_array($material,['mimosa','cane'],true)?60:null], ['frame','connectors','roof','cladding','lashings'], ['metal','metal','mimosa','cane','natural'], ['recovered','new_hardware','controlled','controlled','new_hardware']);
    }
    public function test_seed_preserves_work_and_separates_costs_rentals_and_single_main_sponsor(): void
    {
        $this->seed();
        $project = EventProject::where('slug','os-jogos-do-agricultor')->firstOrFail();
        $scenario = $project->launchScenario();
        $this->assertDatabaseCount('cabin_projects',16);
        $this->assertSame(6,CabinProject::where('supply_mode','team_build')->count());
        $this->assertSame(10,CabinProject::where('supply_mode','qapas_rental')->count());
        $this->assertSame(12,$scenario->budgetLines()->where('costing_key','like','cabin%')->where('kind','cost')->whereNull('unit_gross_cents')->count());
        $this->assertSame(0,(int)$scenario->budgetLines()->where('costing_key','like','cabin%')->where('kind','revenue')->sum('forecast_quantity'));
        $machine=$scenario->budgetLines()->where('costing_key','cabins-shredder-purchase')->sole();
        $this->assertSame('investment',$machine->expense_type);
        $this->assertSame(1,$machine->forecast_quantity);
        $this->assertNull($machine->unit_gross_cents);
        $machineRequest=\App\Models\CostConsultation::where('costable_type',\App\Models\BudgetLine::class)->where('costable_id',$machine->id)->sole();
        $this->assertStringContainsString('devis d’achat d’un broyeur',$machineRequest->body_fr);
        $this->assertStringContainsString('60 mm',$machineRequest->body_pt);
        $this->assertSame(0,(int)$scenario->budgetLines()->sum('paid_quantity'));
        $this->assertSame(6,$project->activities()->where('track','official')->count());
        $this->assertSame('hidden',$project->cabin_visibility);
        $this->assertFalse($scenario->report()['launch_ready']);
        $main = Sponsorship::where('event_project_id',$project->id)->where('scope','main')->sole();
        $this->assertStringContainsString('pièce maîtresse',$main->pitch);
        $this->assertSame('prospecting',$main->status);
        $this->assertFalse($main->agreed());
        $this->assertSame(0,$main->funding()['paid_cents']);
        $rental = CabinProject::where('supply_mode','qapas_rental')->firstOrFail();
        $rental->update(['name'=>'My custom cabin','owner'=>'My supervisor']);
        $rental->costLine->update(['unit_gross_cents'=>12500,'vat_basis_points'=>2300,'price_source'=>'My actual quotation']);
        $main->update(['pitch'=>'My negotiated pitch','status'=>'contacted']);
        $project->update(['cabin_visibility'=>'teaser']);
        $this->seed();
        $this->assertDatabaseCount('cabin_projects',16);
        $this->assertSame('My custom cabin',$rental->fresh()->name);
        $this->assertSame(12500,$rental->fresh()->costLine->unit_gross_cents);
        $this->assertSame('My negotiated pitch',$main->fresh()->pitch);
        $this->assertSame('teaser',$project->fresh()->cabin_visibility);
        $this->assertSame(7,$project->ideas()->where('template_key','like','cabin-%')->count());
        $this->assertStringContainsString('raccord d’échafaudage',\App\Models\CostConsultation::where('event_project_id',$project->id)->where('costable_id',$rental->cost_line_id)->where('costable_type',\App\Models\BudgetLine::class)->firstOrFail()->body_fr);
    }
    public function test_server_requires_recovery_suitable_materials_dimensions_and_same_edition(): void
    {
        $project = $this->project(); $cabin = $this->cabin($project);
        $rows = $this->inventory(); $cabin->update(['materials'=>$rows]);
        $straw=['name'=>'Recovered straw roof','part'=>'roof_cover','material'=>'straw','source'=>'recovered','quantity'=>1,'unit'=>'lot','origin'=>'Clean agricultural residue'];
        $cabin->update(['materials'=>[...$rows,$straw]]);
        $this->invalid(fn()=>$cabin->fresh()->update(['materials'=>[array_replace($straw,['material'=>'plastic'])]]));
        foreach ([['frame','metal','new_hardware'],['roof','plastic','recovered'],['cladding','wood','recovered'],['decoration','asbestos','recovered'],['roof','mimosa','recovered'],['lashings','metal','new_hardware']] as [$part,$material,$source]) {
            $bad = array_replace($rows[0],compact('part','material','source'));
            $this->invalid(fn()=>$cabin->fresh()->update(['materials'=>[$bad]]));
        }
        $tooThick=$rows; $tooThick[2]['max_diameter_mm']=61;
        $this->invalid(fn()=>$cabin->fresh()->update(['materials'=>$tooThick]));
        $unknownDiameter=$rows; unset($unknownDiameter[3]['max_diameter_mm']);
        $this->invalid(fn()=>$cabin->fresh()->update(['materials'=>$unknownDiameter]));
        $this->invalid(fn()=>$cabin->fresh()->update(['width_mm'=>2500]));
        $this->invalid(fn()=>$cabin->fresh()->update(['width_mm'=>4800]));
        $cabin->fresh()->update(['depth_mm'=>3500]);
        $this->assertSame(3500,$cabin->fresh()->depth_mm);
        $this->assertSame(30,$cabin->fresh()->frame_diameter_mm);
        $this->invalid(fn()=>$cabin->fresh()->update(['supply_mode'=>'qapas_rental']));
        $this->invalid(fn()=>CabinProject::create(['event_project_id'=>$this->project('other')->id,'stand_id'=>$cabin->stand_id,'name'=>'Wrong edition']));
        $this->invalid(fn()=>CabinProject::create(['event_project_id'=>$project->id,'stand_id'=>$cabin->stand_id,'name'=>'Duplicate cabin']));
    }
    public function test_rental_cannot_be_counted_twice_and_linked_budget_lines_cannot_change_meaning(): void
    {
        $project = $this->project(); $cabin = $this->cabin($project,true);
        $scenario = $project->scenarios()->create(['name'=>'Budget']);
        // This new line must not match the NULL rental_line_id of an unrelated cabin.
        $this->cabin($project);
        $cost = $scenario->budgetLines()->create(['name'=>'Fabrication','kind'=>'cost','scope'=>'stand','stand_id'=>$cabin->stand_id,'forecast_quantity'=>1,'unit_gross_cents'=>30000,'vat_basis_points'=>2300]);
        $rent = $scenario->budgetLines()->create(['name'=>'Separate rent','kind'=>'revenue','scope'=>'stand','stand_id'=>$cabin->stand_id,'forecast_quantity'=>0,'unit_gross_cents'=>5000,'vat_basis_points'=>2300]);
        $cabin->update(['cost_line_id'=>$cost->id,'rental_line_id'=>$rent->id,'rental_pricing'=>'included','deposit_cents'=>5000]);
        $this->invalid(fn()=>$rent->update(['forecast_quantity'=>1]));
        $this->invalid(fn()=>$cost->update(['kind'=>'revenue']));
        $this->invalid(fn()=>$rent->fresh()->update(['stand_id'=>$project->stands()->where('kind','village')->first()->id]));
        $this->invalid(fn()=>$cabin->fresh()->update(['rental_pricing'=>'extra']));
        $cabin->update(['rental_pricing'=>'extra','rental_terms'=>'Additional rental, package excludes cabin. Return and deposit documented.']);
        $rent->fresh()->update(['forecast_quantity'=>1]);
        $this->invalid(fn()=>$cabin->fresh()->update(['rental_pricing'=>'included']));
        $otherScenario=$project->scenarios()->create(['name'=>'Variant']);
        $otherRent=$otherScenario->budgetLines()->create(['name'=>'Other rent','kind'=>'revenue','scope'=>'stand','stand_id'=>$cabin->stand_id,'forecast_quantity'=>0]);
        $this->invalid(fn()=>$cabin->fresh()->update(['rental_line_id'=>$otherRent->id]));
        $this->assertSame(5000,$cabin->fresh()->deposit_cents);
        $this->assertSame(1,$rent->fresh()->forecast_quantity);
        $this->assertSame(0,$scenario->budgetLines()->where('kind','deposit')->count());
    }
    public function test_reception_requires_evidence_and_is_invalidated_by_material_or_placement_changes(): void
    {
        $project = $this->project(); $cabin = $this->cabin($project);
        $scenario = $project->scenarios()->create(['name'=>'Pilot']); $scenario->includedStands()->attach($cabin->stand_id);
        $this->admin();
        $this->invalid(fn()=>$cabin->update(['status'=>'received']));
        $quartel = $project->siteFeatures()->create(['name'=>'Terrace','category'=>'quartel','geometry'=>['type'=>'Polygon','coordinates'=>[[[-7.4,40.3],[-7.39,40.3],[-7.39,40.31],[-7.4,40.31],[-7.4,40.3]]]]]);
        $cabin->stand->update(['quartel_id'=>$quartel->id,'pitch_number'=>'A1']);
        $cabin = $cabin->fresh();
        $cabin->update(['status'=>'ready','frame_spacing_mm'=>1200,'materials'=>$this->inventory(),'owner'=>'Supervisor','harvest_origin'=>'On-site control plot','control_plan'=>'Documented inspection and preparation without viable fragments','follow_up_owner'=>'Gardener','follow_up_on'=>today()->addMonth(),'reception_evidence'=>'On-site technical review reference and author']);
        $this->invalid(fn()=>$cabin->fresh()->update(['status'=>'received']));
        $cabin->fresh()->update(['structural_reviewer'=>'Internal test reviewer','structural_reviewed_on'=>today(),'structural_evidence'=>'Internal assessment reference for the exact frame and roof configuration','status'=>'received']);
        $this->assertTrue($cabin->fresh()->received());
        $this->assertStringNotContainsString('cabane à implanter',implode(' ',app(Readiness::class)->blockers($project->fresh(),'live')));
        $cabin->stand->update(['pitch_number'=>'A2']);
        $this->assertFalse($cabin->fresh()->received());
        $this->assertStringContainsString('cabane à implanter',implode(' ',app(Readiness::class)->blockers($project->fresh(),'live')));
        $cabin->fresh()->update(['status'=>'ready']); $cabin->fresh()->update(['status'=>'received']);
        $this->assertTrue($cabin->fresh()->received());
        $rows=$this->inventory(); $rows[0]['quantity']=2;
        $cabin->fresh()->update(['materials'=>$rows]);
        $this->assertSame('ready',$cabin->fresh()->status);
        $this->assertNull($cabin->fresh()->reviewed_at);
        $cabin->fresh()->update(['depth_mm'=>3500]);
        $this->assertTrue($cabin->fresh()->extended());
        $this->invalid(fn()=>$cabin->fresh()->update(['status'=>'received']));
        $cabin->fresh()->update(['frame_spacing_mm'=>1200]);
        $cabin->fresh()->update(['structural_reviewer'=>'Qualified test reviewer','structural_reviewed_on'=>today(),'structural_evidence'=>'Test fixture only: calculation for current dimensions, joints, loads, anchors and site']);
        $cabin->fresh()->update(['status'=>'received']);
        $this->assertTrue($cabin->fresh()->received());
        $cabin->fresh()->update(['depth_mm'=>5000]);
        $this->assertFalse($cabin->fresh()->received());
        $this->assertNull($cabin->fresh()->structural_reviewed_on);
        $this->invalid(fn()=>$cabin->fresh()->update(['status'=>'received']));

    }
    public function test_public_disclosure_is_staged_and_never_exposes_inventory_or_cost_notes(): void
    {
        $project=$this->project(); $cabin=$this->cabin($project);
        $cabin->update(['is_public'=>true,'name'=>'Visible cabin','summary_fr'=>'<script>secretScript()</script>','materials'=>$this->inventory(),'owner'=>'PRIVATE-OWNER','participant_cost_evidence'=>'PRIVATE-COST']);
        $url='/events/test/cabanes';
        $this->get($url)->assertNotFound();
        $project->update(['cabin_visibility'=>'teaser']);
        $this->get($url)->assertOk()->assertDontSee('Visible cabin')->assertDontSee('échafaudage');
        $project->update(['cabin_visibility'=>'details']);
        $this->get($url)->assertOk()->assertSee('Visible cabin')->assertSee('échafaudage')->assertDontSee('<script>secretScript()',false)->assertDontSee('PRIVATE-OWNER')->assertDontSee('PRIVATE-COST')->assertDontSee('PRIVATE-STOCK');
        $this->withSession(['locale'=>'pt'])->get($url)->assertOk()->assertSee('abraçadeira de andaime')->assertSee('cabana QAPAS para alugar');
        $this->withSession(['locale'=>'fr']);
        $cabin->stand->update(['is_public'=>false]);
        $this->get($url)->assertOk()->assertDontSee('Visible cabin');
        $this->get('/workspace/preview/test/cabanes')->assertNotFound();
        $this->app->instance('env','local');
        $this->get('/workspace/preview/test/cabanes')->assertForbidden();
        $admin=$this->admin();
        $this->get('/workspace/preview/test/cabanes')->assertOk()->assertSee('Visible cabin')->assertHeader('Cache-Control','no-store, private')->assertDontSee('PRIVATE-STOCK');
        $admin->update(['is_active'=>false]);
        $this->get('/workspace/preview/test/cabanes')->assertForbidden();
    }

    public function test_demo_video_stays_draft_until_published_and_follows_challenge_disclosure(): void
    {
        $project=$this->project(); $project->update(['cabin_visibility'=>'details']);
        $post=\App\Models\EditorialPost::create(['event_project_id'=>$project->id,'template_key'=>'cabin-demo','name'=>'Construction demo','title_pt'=>'Demonstração','body_fr'=>'Prototype approved','body_pt'=>'Protótipo aprovado','youtube_id'=>'abcdEFgh123']);
        $this->get('/events/test/cabanes')->assertOk()->assertSee('vidéo de démonstration')->assertDontSee($post->public_id);
        $post->update(['rights_evidence'=>'Image and music rights recorded','status'=>'published','published_at'=>now()]);
        $this->get('/events/test/cabanes')->assertOk()->assertSee($post->public_id);
        $url=route('blog.show',['project'=>$project->slug,'post'=>$post->public_id]);
        $this->get($url)->assertOk()->assertSee('abcdEFgh123');
        $project->update(['cabin_visibility'=>'teaser']);
        $this->get($url)->assertNotFound();
        $this->get('/events/test/cabanes')->assertOk()->assertDontSee($post->public_id);
        $this->get(route('blog.index',['project'=>$project->slug]))->assertOk()->assertDontSee('Construction demo');
    }

    public function test_filament_form_validation_and_filters_require_active_admin(): void
    {
        $project=$this->project(); $stand=$project->stands()->create(['name'=>'Village stand','kind'=>'village']);
        $this->get(CabinProjectResource::getUrl())->assertRedirect();
        $admin=$this->admin();
        $data=['event_project_id'=>$project->id,'stand_id'=>$stand->id,'name'=>'Form cabin','supply_mode'=>'team_build','status'=>'concept','width_mm'=>2400,'height_mm'=>2400,'depth_mm'=>2400,'rental_pricing'=>'unpriced','materials'=>[]];
        Livewire::test(ManageRecords::class)->callAction('create',data:array_replace($data,['width_mm'=>100]))->assertHasActionErrors(['width_mm']);
        Livewire::test(ManageRecords::class)->callAction('create',data:$data)->assertHasNoActionErrors();
        $cabin=CabinProject::firstOrFail();
        $foreign=$this->cabin($this->project('other'),true);
        Livewire::test(ManageRecords::class)->filterTable('supply_mode','team_build')->assertCanSeeTableRecords([$cabin])->assertCanNotSeeTableRecords([$foreign]);
        $this->get(CabinProjectResource::getUrl())->assertOk();
        $guide=Livewire::test(ManageRecords::class)->mountAction('structure')->assertActionMounted('structure');
        $this->assertStringContainsString('non dimensionné',$guide->instance()->getMountedAction()->getModalContent()->toHtml());
        $admin->update(['is_active'=>false]);
        $this->get(CabinProjectResource::getUrl())->assertForbidden();
    }
}
