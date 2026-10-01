<?php
namespace Tests\Feature;

use App\Domain\Finance\CommercialPricing;
use App\Domain\Promotion\StandSponsoring;
use App\Models\{Admin,EventProject,Stand,StandPartner,Sponsorship,Interest,CommercialPlan};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SponsorshipCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function project(string $slug='sponsorship'): EventProject
    {
        return EventProject::create(['name'=>'Games','slug'=>$slug,'is_public'=>true,'sponsorship_visibility'=>'catalog','sponsorship_policy_version'=>'stand-shares-v1']);
    }
    private function stand(EventProject $project,string $kind='village'): Stand
    {
        return $project->stands()->create(['name'=>'Stand '.$kind,'kind'=>$kind,'is_public'=>true]+($kind==='village'?['sponsorship_total_cents'=>50000,'sponsorship_price_evidence'=>'Commercial hypothesis, not break-even']:[]));
    }
    private function partner(Stand $stand,int $units=1,array $extra=[]): StandPartner
    {
        return $stand->partners()->create(array_replace(['name'=>'Private company','share_units'=>$units,'exclusive_sponsorship'=>$units===5,'status'=>'agreed','evidence'=>'Private agreement reference'],$extra));
    }
    private function invalid(callable $f): void
    {
        try {$f();$this->fail('Expected validation');} catch (ValidationException) {$this->assertTrue(true);}
    }
    private function admin(): Admin
    {
        $admin=Admin::create(['name'=>'Admin','email'=>'sponsorship@example.test','password'=>'long-sponsorship-password']);
        $this->actingAs($admin,'admin');return $admin;
    }
    private function privacy(): void
    {
        config(['events.privacy_ready'=>true,'events.organizer_name'=>'QAPAS','events.contact_email'=>'contact@example.test']);
    }
    private function requestData(Stand $stand,int $units=1): array
    {
        return ['target'=>'stand','stand'=>$stand->public_id,'package'=>$units,'name'=>'Interested sponsor','email'=>'sponsor@example.test','privacy'=>'1','quoted_gross_cents'=>1];
    }

    public function test_seed_adds_four_included_structural_stands_and_keeps_costs_unresolved_without_double_rental(): void
    {
        $this->seed();$project=EventProject::where('slug','os-jogos-do-agricultor')->sole();$scenario=$project->launchScenario();
        $this->assertCount(16,$scenario->includedStands);$this->assertSame(12,$scenario->stand_target);$this->assertSame(4,$scenario->sponsor_stand_target);
        $this->assertSame(6,$scenario->includedStands->where('kind','village')->count());
        $this->assertSame(6,$scenario->includedStands->where('kind','independent')->count());
        $this->assertSame('hidden',$project->sponsorship_visibility);
        $sponsors=Sponsorship::whereIn('scope',['main','secondary'])->get();$this->assertCount(4,$sponsors);
        foreach($sponsors as $sponsor){
            $stand=$sponsor->stand;$this->assertSame('sponsor',$stand->kind);$this->assertTrue($scenario->includedStands->contains('id',$stand->id));
            $this->assertSame('included',$stand->cabinProject->rental_pricing);
            $this->assertNull($stand->cabinProject->costLine->unit_gross_cents);
            $this->assertSame(0,$stand->cabinProject->rentalLine->forecast_quantity);
            $services=$scenario->budgetLines()->where('costing_key','sponsor-stand-services-'.$stand->public_id)->sole();
            $this->assertNull($services->unit_gross_cents);$this->assertSame(1,$services->forecast_quantity);
            $this->assertSame(0,(int)$scenario->budgetLines()->where('stand_id',$stand->id)->where('kind','revenue')->sum('forecast_quantity'));
        }
        $plan=CommercialPlan::firstOrFail();$this->assertSame(100000,$plan->village_price_cents);$this->assertLessThan(0,$plan->report()['headroom_cents']);
        $this->assertFalse($scenario->report()['launch_ready']);$this->assertSame(0,(int)$scenario->budgetLines()->sum('paid_quantity'));
        $village=$project->stands()->where('kind','village')->first();$village->update(['sponsorship_total_cents'=>60000,'sponsorship_price_evidence'=>'Custom proposal']);
        $sponsors->first()->update(['catalog_fr'=>'My negotiated deliverables']);$project->update(['sponsorship_visibility'=>'catalog']);
        $this->seed();$this->assertDatabaseCount('stands',16);$this->assertSame(60000,$village->fresh()->sponsorship_total_cents);
        $this->assertSame('My negotiated deliverables',$sponsors->first()->fresh()->catalog_fr);$this->assertSame('catalog',$project->fresh()->sponsorship_visibility);
    }

    public function test_five_share_limit_legacy_exclusivity_and_agreed_price_snapshots(): void
    {
        $stand=$this->stand($this->project());$service=app(StandSponsoring::class);
        $this->assertSame(10000,$service->quote($stand,1,false));$this->assertSame(50000,$service->quote($stand,5,true));
        $this->partner($stand,5,['status'=>'prospect']);$this->assertSame(5,$service->report($stand)['available']);
        $a=$this->partner($stand,2);$this->assertSame(50000,$a->reference_total_cents);$this->assertSame(3,$service->report($stand)['available']);
        $this->invalid(fn()=>$this->partner($stand,4));$this->invalid(fn()=>$this->partner($stand,5));
        $this->invalid(fn()=>$service->quote($stand,5,false));
        $stand->update(['sponsorship_total_cents'=>75000]);$a->refresh()->update(['name'=>'Same agreement, updated contact']);
        $this->assertSame(50000,$a->fresh()->reference_total_cents);$this->assertSame(45000,$service->quote($stand,3,false));
        $this->invalid(fn()=>$a->fresh()->update(['share_units'=>3]));
        $b=$this->partner($stand,3);$this->assertSame(0,$service->report($stand)['available']);
        $b->update(['status'=>'cancelled']);$a->fresh()->update(['status'=>'cancelled']);
        $legacy=$stand->partners()->create(['name'=>'Legacy exclusive','main_slot'=>1,'status'=>'agreed']);
        $this->assertSame(0,$service->report($stand)['available']);$this->invalid(fn()=>$this->partner($stand,1));
        $legacy->update(['status'=>'cancelled']);$this->partner($stand,5);$this->assertSame(0,$service->report($stand)['available']);
        $this->assertDatabaseCount('budget_lines',0);
    }

    public function test_applying_commercial_prices_keeps_existing_local_agreements_and_budget(): void
    {
        $this->seed();$this->admin();$plan=CommercialPlan::firstOrFail();
        $line=app(CommercialPricing::class)->lines($plan)->first(fn($line)=>$line->stand->kind==='village');
        $partner=$this->partner($line->stand,2);$price=$line->unit_gross_cents;
        $plan->update(['village_price_cents'=>60000]);
        $this->invalid(fn()=>app(CommercialPricing::class)->apply($plan));
        $this->assertSame($price,$line->fresh()->unit_gross_cents);
        $this->assertSame(100000,$partner->fresh()->reference_total_cents);
        $this->assertSame(100000,$line->stand->fresh()->sponsorship_total_cents);
    }

    public function test_structural_stand_is_same_edition_unique_and_required_before_agreement(): void
    {
        $project=$this->project();$own=$this->stand($project,'sponsor');$foreign=$this->stand($this->project('foreign'),'sponsor');$village=$this->stand($project);
        $sponsor=Sponsorship::create(['event_project_id'=>$project->id,'name'=>'Principal','scope'=>'main']);
        $agreement=['status'=>'agreed','sponsor_name'=>'Partner','agreed_at'=>now(),'agreement_evidence'=>'Signed','web_rights'=>'Web and own stand','cash_pledged_cents'=>250000];
        $this->invalid(fn()=>$sponsor->update($agreement));
        $this->invalid(fn()=>$sponsor->fresh()->update(['stand_id'=>$foreign->id]));
        $this->invalid(fn()=>$sponsor->fresh()->update(['stand_id'=>$village->id]));
        $sponsor->refresh()->update(['stand_id'=>$own->id]+$agreement);$this->assertTrue($sponsor->fresh()->agreed());
        $this->invalid(fn()=>Sponsorship::create(['event_project_id'=>$project->id,'name'=>'Other','scope'=>'secondary','secondary_slot'=>1,'stand_id'=>$own->id]));
        $this->invalid(fn()=>$own->update(['kind'=>'independent']));
        $duplicate=Sponsorship::create(['event_project_id'=>$project->id,'name'=>'Alternative pitch','scope'=>'main']);$this->assertFalse($duplicate->catalogAvailable());
        $incomplete=Sponsorship::create(['event_project_id'=>$project->id,'name'=>'Undefined secondary','scope'=>'secondary']);$this->assertFalse($incomplete->catalogAvailable());
    }

    public function test_public_catalog_respects_disclosure_private_names_and_local_preview_authorization(): void
    {
        $project=$this->project();$stand=$this->stand($project);$partner=$this->partner($stand,2);
        $slot=Sponsorship::create(['event_project_id'=>$project->id,'name'=>'PRIVATE-PROSPECT','scope'=>'main','stand_id'=>$this->stand($project,'sponsor')->id,'catalog_visible'=>true,'catalog_fr'=>'Public package','catalog_pt'=>'Proposta pública','pitch'=>'PRIVATE-PITCH']);
        $activity=$project->activities()->create(['name'=>'SECRET-CHALLENGE','rules'=>'Private draft rules','track'=>'official','is_public'=>false]);
        Sponsorship::create(['event_project_id'=>$project->id,'name'=>'Secret slot','scope'=>'activity','activity_id'=>$activity->id,'catalog_visible'=>true]);
        $url='/events/sponsorship/parrainer';
        $this->get($url.'?stand='.$stand->public_id)->assertOk()->assertSee('40 %')->assertSee('Attribuée')->assertDontSee('Private company')->assertDontSee('Private agreement reference');
        $partner->update(['is_public'=>true,'name'=>'<script>alert(1)</script>']);
        $this->get($url.'?stand='.$stand->public_id)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;',false)->assertDontSee('<script>alert(1)</script>',false);
        $this->get($url.'?target=event')->assertOk()->assertSee('Public package')->assertDontSee('PRIVATE-PROSPECT')->assertDontSee('PRIVATE-PITCH')->assertDontSee('SECRET-CHALLENGE');
        $legacyOffer=$project->offers()->create(['name'=>'Old structural card','includes'=>'Old description','kind'=>'sponsor','is_public'=>true,'preview_current'=>true]);
        $this->get(route('offer.show',['project'=>$project->slug,'offer'=>$legacyOffer->public_id]))->assertRedirect(route('sponsoring.index',['project'=>$project->slug,'target'=>'event']));
        $this->get(route('event.show',['project'=>$project->slug]))->assertOk()->assertDontSee('Old structural card');
        $this->withSession(['locale'=>'pt'])->get($url.'?target=event')->assertOk()->assertSee('Proposta pública');
        $slot->update(['catalog_visible'=>false]);$this->get($url.'?slot='.$slot->public_id)->assertNotFound();
        $project->update(['sponsorship_visibility'=>'hidden']);$this->get($url)->assertNotFound();
        $preview='/workspace/preview/sponsorship/parrainer?target=event';$this->get($preview)->assertNotFound();
        $this->app->instance('env','local');$this->get($preview)->assertForbidden();$admin=$this->admin();
        $this->get($preview)->assertOk()->assertSee('SECRET-CHALLENGE')->assertDontSee('PRIVATE-PITCH')->assertHeader('Cache-Control','no-store, private');
        $admin->update(['is_active'=>false]);$this->get($preview)->assertForbidden();
    }

    public function test_interest_quotes_are_server_calculated_and_do_not_reserve_or_charge(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $project=$this->project();$stand=$this->stand($project);$url='/events/sponsorship/parrainer';$this->privacy();
        $this->post($url,$this->requestData($stand,2))->assertRedirect();
        $interest=Interest::sole();$this->assertSame(20000,$interest->sponsorship_request['quoted_gross_cents']);$this->assertSame(40,$interest->sponsorship_request['percentage']);
        $this->assertFalse($interest->sponsorship_request['exclusive']);$this->assertFalse($interest->marketing_opt_in);$this->assertNotNull($interest->privacy_acknowledged_at);
        $this->assertSame(5,app(StandSponsoring::class)->report($stand)['available']);$this->assertDatabaseCount('stand_partners',0);$this->assertDatabaseCount('budget_lines',0);
        $this->post($url,$this->requestData($stand,5))->assertRedirect();$this->assertTrue(Interest::latest('id')->first()->sponsorship_request['exclusive']);
        $this->postJson($url,$this->requestData($stand,6))->assertUnprocessable();
        $this->postJson($url,array_replace($this->requestData($stand),['privacy'=>null]))->assertUnprocessable();
        $this->partner($stand,5);$this->postJson($url,$this->requestData($stand))->assertUnprocessable();
        $foreign=$this->stand($this->project('elsewhere'));$this->post($url,$this->requestData($foreign))->assertNotFound();
        $this->assertDatabaseCount('interests',2);
    }

    public function test_event_inquiry_rechecks_slot_availability_and_privacy_gate(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $project=$this->project();$slot=Sponsorship::create(['event_project_id'=>$project->id,'name'=>'Principal','scope'=>'main','stand_id'=>$this->stand($project,'sponsor')->id,'catalog_visible'=>true,'catalog_price_cents'=>250000]);
        $url='/events/sponsorship/parrainer';$data=['target'=>'event','slot'=>$slot->public_id,'name'=>'Sponsor','email'=>'event@example.test','privacy'=>'1'];
        config(['events.privacy_ready'=>false]);$this->post($url,$data)->assertStatus(503);$this->assertDatabaseCount('interests',0);
        $this->privacy();$this->post($url,$data)->assertRedirect();$request=Interest::sole()->sponsorship_request;
        $this->assertSame(250000,$request['quoted_gross_cents']);$this->assertTrue($request['stand_included']);$this->assertSame('prospecting',$slot->fresh()->status);
        $slot->update(['catalog_visible'=>false]);$this->post($url,$data)->assertNotFound();
        $slot->update(['catalog_visible'=>true,'status'=>'agreed','sponsor_name'=>'Confirmed sponsor','agreed_at'=>now(),'agreement_evidence'=>'Signed','web_rights'=>'Web','cash_pledged_cents'=>250000]);
        $this->postJson($url,$data)->assertUnprocessable();$this->assertDatabaseCount('interests',1);
    }

    public function test_filament_share_form_and_sponsor_stand_selector_render_and_validate(): void
    {
        $stand=$this->stand($this->project());$this->get(\App\Filament\Resources\StandPartnerResource::getUrl())->assertRedirect();$this->admin();
        $page=\App\Filament\Resources\StandPartnerResource\Pages\ManageRecords::class;
        \Livewire\Livewire::test($page)->callAction('create',data:['stand_id'=>$stand->id,'name'=>'Shared sponsor','share_units'=>2,'exclusive_sponsorship'=>false,'status'=>'prospect'])->assertHasNoActionErrors();
        $partner=StandPartner::sole();$this->assertSame(2,$partner->share_units);
        \Livewire\Livewire::test($page)->mountAction(\Filament\Actions\Testing\TestAction::make('edit')->table($partner))->assertHasNoActionErrors();
        foreach(['Sponsorship','Stand','CommercialPlan'] as $name){$resource='App\\Filament\\Resources\\'.$name.'Resource';$this->get($resource::getUrl())->assertOk();\Livewire\Livewire::test($resource.'\\Pages\\ManageRecords')->mountAction('create')->assertHasNoActionErrors();}
        $this->get(\App\Filament\Resources\InterestResource::getUrl())->assertOk();
    }
}
