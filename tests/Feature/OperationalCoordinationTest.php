<?php
namespace Tests\Feature;

use App\Domain\Finance\{CommercialPricing, Money};
use App\Domain\Planning\Readiness;
use App\Domain\Teams\ExpertRoles;
use App\Filament\Resources\RunItemResource;
use App\Filament\Resources\RunItemResource\Pages\ManageRecords;
use App\Models\{Admin, CommercialPlan, EventProject};
use Database\Seeders\OperationalVocabularySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class OperationalCoordinationTest extends TestCase
{
    use RefreshDatabase;

    private function project(string $slug = 'os-jogos-do-agricultor'): EventProject
    {
        return EventProject::create(['name' => 'Event', 'slug' => $slug]);
    }

    private function admin(): Admin
    {
        $admin = Admin::create(['name' => 'Coordination', 'email' => 'coordination@example.test', 'password' => 'a-long-testing-password']);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    private function invalid(callable $action): void
    {
        try { $action(); $this->fail('Validation expected'); }
        catch (ValidationException) { $this->assertTrue(true); }
    }

    public function test_schedule_is_chronological_filterable_and_admin_only(): void
    {
        $project = $this->project();
        $admin = $this->admin();
        $late = $project->runItems()->create(['name' => 'Closing', 'owner' => 'Private supervisor', 'location' => 'Private zone', 'starts_at' => '2026-12-12 22:00', 'ends_at' => '2026-12-12 22:30', 'status' => 'done']);
        $early = $project->runItems()->create(['name' => 'Opening', 'owner' => 'Other supervisor', 'starts_at' => '2026-12-12 10:00', 'ends_at' => '2026-12-12 10:30']);
        $foreign = $this->project('other')->runItems()->create(['name' => 'Other edition', 'owner' => 'Other', 'starts_at' => '2026-12-12 08:00', 'ends_at' => '2026-12-12 09:00']);
        Livewire::test(ManageRecords::class)
            ->filterTable('event_project_id', $project->id)
            ->assertCanSeeTableRecords([$early, $late], inOrder: true)
            ->assertCanNotSeeTableRecords([$foreign])
            ->filterTable('status', 'done')
            ->assertCanSeeTableRecords([$late])->assertCanNotSeeTableRecords([$early]);
        $help = Livewire::test(ManageRecords::class)->mountAction('coordination')->assertActionMounted('coordination');
        $this->assertStringContainsString('Il n’existe pas encore de messagerie terrain', $help->instance()->getMountedAction()->getModalContent()->render());
        $this->get(RunItemResource::getUrl())->assertOk()->assertSee('Déroulé opérationnel')->assertDontSee('Le conducteur est');
        $admin->update(['is_active' => false]);
        $this->get(RunItemResource::getUrl())->assertForbidden();
        auth('admin')->logout();
        $this->get(RunItemResource::getUrl())->assertRedirect();
    }

    public function test_schedule_form_and_server_reject_invalid_times_and_edition_changes(): void
    {
        $project = $this->project();
        $this->admin();
        $data = ['event_project_id' => $project->id, 'name' => 'Summer rehearsal', 'owner' => 'Supervisor', 'location' => 'Meeting place', 'starts_at' => '2026-07-12 10:00:00', 'ends_at' => '2026-07-12 11:00:00', 'status' => 'planned'];
        Livewire::test(ManageRecords::class)->callAction('create', data: array_replace($data, ['ends_at' => '2026-07-12 09:00:00']))->assertHasActionErrors(['ends_at']);
        Livewire::test(ManageRecords::class)->callAction('create', data: $data)->assertHasNoActionErrors();
        $item = $project->runItems()->firstOrFail();
        // Summer Lisbon is UTC+1; storage remains UTC.
        $this->assertSame('2026-07-12 09:00:00', $item->starts_at->format('Y-m-d H:i:s'));
        $this->invalid(fn () => $item->update(['ends_at' => $item->starts_at]));
        $item->refresh();
        $this->invalid(fn () => $item->update(['status' => 'acknowledged']));
        $item->refresh();
        $this->invalid(fn () => $item->update(['owner' => '']));
        $item->refresh();
        $this->invalid(fn () => $item->update(['event_project_id' => $this->project('other')->id]));
    }

    public function test_vocabulary_update_preserves_assignments_custom_text_and_archives(): void
    {
        $project = $this->project();
        $team = $project->teams()->create(['name' => 'Team', 'freguesia' => 'Commune']);
        $slot = $team->roleAssignments()->where('role_code', 'accountant')->firstOrFail();
        $slot->update(['status' => 'proposed', 'candidate_name' => 'Existing candidate']);
        $old = 'Six rôles indispensables (cuisinier, pelliste, tracteur, musicien, comptable, athlète)';
        $active = $project->scenarios()->create(['name' => 'Active', 'assumptions' => $old.'. Texte personnel conservé.']);
        $archive = $project->scenarios()->create(['name' => 'Archive', 'assumptions' => $old, 'is_archived' => true]);
        $idea = $project->ideas()->create(['name' => 'Live', 'template_key' => 'event-live', 'pillar' => 'product', 'status' => 'idea', 'hypothesis' => 'The crew follows a shared schedule.', 'experiment' => 'Utiliser le conducteur ; valider résultats, portions et images avant communiqué.']);
        $this->seed(OperationalVocabularySeeder::class);
        $this->seed(OperationalVocabularySeeder::class);
        $this->assertStringContainsString('gardien des comptes', $active->fresh()->assumptions);
        $this->assertStringContainsString('Texte personnel conservé.', $active->fresh()->assumptions);
        $this->assertSame($old, $archive->fresh()->assumptions);
        $this->assertStringContainsString('déroulé opérationnel', $idea->fresh()->experiment);
        $idea->update(['experiment' => 'Ma consigne personnalisée']);
        $this->seed(OperationalVocabularySeeder::class);
        $this->assertSame('Ma consigne personnalisée', $idea->fresh()->experiment);
        $this->assertSame('Existing candidate', $slot->fresh()->candidate_name);
        $this->assertSame('proposed', $slot->fresh()->status);
        $this->assertCount(14, $team->fresh()->roleAssignments);
        $this->assertSame('Gardien des comptes', ExpertRoles::labels('fr')['accountant']);
        $this->assertSame('Guardião das contas', ExpertRoles::labels('pt')['accountant']);
        $this->assertContains('accountant', ExpertRoles::CORE);
        foreach (['fr', 'pt'] as $locale) {
            $this->assertStringContainsString(mb_strtolower(ExpertRoles::labels($locale)['accountant']), __('community.roles_intro', [], $locale));
        }
    }

    public function test_demo_teams_do_not_block_live_readiness_but_real_teams_still_do(): void
    {
        $project = $this->project();
        $project->teams()->create(['name' => 'Fictitious team', 'freguesia' => 'Commune', 'is_demo' => true, 'status' => 'forming']);
        $project->teams()->create(['name' => 'Real team', 'freguesia' => 'Commune', 'status' => 'forming']);
        $blockers = implode(' ', app(Readiness::class)->blockers($project->fresh(), 'live'));
        $this->assertStringNotContainsString('Fictitious team', $blockers);
        $this->assertStringContainsString('Real team : rôles indispensables', $blockers);
    }

    public function test_withdrawn_proposal_no_longer_counts_twelve_local_sponsors(): void
    {
        $this->seed();
        $plan = CommercialPlan::firstOrFail();
        $report = $plan->report();
        $this->assertSame(1516134, $report['known_outlay_cents']);
        $this->assertSame(200000, $report['unknown_allowance_cents']);
        $this->assertSame(948365, $report['fixed_net_cents']);
        $net = $report['fixed_net_cents'] + 6 * Money::net(50000, 2300) + 6 * Money::net(100000, 2300);
        $outlay = $report['known_outlay_cents'] + $report['unknown_allowance_cents'];
        $this->assertSame(1680071, $net);
        $this->assertSame(-36063, $net - $outlay);
        $this->assertSame(167937, $net - $outlay + 144000 + 60000);
        $this->assertSame(65000, $plan->fresh()->independent_price_cents);
        $this->assertSame(175000, $report['village_unit_cents']);
        $this->assertSame(400000, $report['target_cents']);
        $this->assertSame(0, app(CommercialPricing::class)->lines($plan)->sum('paid_quantity'));
        $this->assertFalse($plan->scenario->report()['launch_ready']);
    }
}
