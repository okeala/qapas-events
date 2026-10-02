<?php

namespace Tests\Feature;

use App\Filament\Pages\EventBalance;
use App\Filament\Pages\EventCommunications;
use App\Filament\Pages\EventCosts;
use App\Filament\Pages\EventPlan;
use App\Filament\Pages\EventProducts;
use App\Filament\Pages\EventRevenues;
use App\Models\Admin;
use App\Models\EventBooking;
use App\Models\EventOffer;
use App\Models\EventRefund;
use App\Models\EventScenario;
use App\Models\PlanElement;
use App\Models\PlannerEvent;
use App\Services\Events\BudgetWriter;
use App\Services\Events\ConditionalBookings;
use App\Services\Events\EventAccess;
use App\Services\Events\EventFinance;
use App\Services\Events\Money;
use App\Services\Events\PlanGeometry;
use App\Services\Events\PlanWriter;
use App\Services\Events\PublicPlan;
use App\Services\Events\ScenarioCloner;
use App\Services\Events\StockLedger;
use Database\Seeders\FarmersGamesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EventPlannerTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(bool $ready = false): EventScenario
    {
        $event = PlannerEvent::create(['name' => 'Test événement', 'slug' => 'test-'.Str::lower(Str::random(8)), 'phase' => 'applications',
            'settings' => ['phase_copy' => ['applications' => ['fr' => ['title' => 'Candidatures ouvertes', 'message' => 'Une proposition avant décision.']],
                'official' => ['fr' => ['title' => 'Événement confirmé', 'message' => 'Rendez-vous sur le plan.']]]]]);

        return $event->scenarios()->create(['name' => 'Base', 'is_base' => true, 'center_lat' => 40.33, 'center_lng' => -7.35,
            'prerequisites' => array_fill_keys(array_keys(config('event_planner.prerequisites')), $ready), 'readiness_reference' => $ready ? 'dossier:test' : null]);
    }

    private function element(EventScenario $s, array $extra = []): PlanElement
    {
        return app(PlanWriter::class)->save($s, array_replace_recursive(['name' => 'Stand', 'category' => 'structures', 'subcategory' => 'independent_stand',
            'shape' => 'square', 'dimensions' => ['lat' => 40.33, 'lng' => -7.35, 'width' => 2.4], 'color' => '#217846', 'is_public' => true, 'is_essential' => true,
            'four_ps' => ['product' => 'Produit privé', 'price' => 'Marge confidentielle'], 'public_content' => ['description' => 'Présentation publique', 'public_price' => 'Entrée libre']], $extra));
    }

    private function offer(EventScenario $s, array $extra = []): EventOffer
    {
        return $s->offers()->create(array_replace(['name' => 'Offre', 'audience' => 'exhibitors', 'price_cents' => 25000, 'net_price_cents' => 20000,
            'delivery_cost_cents' => 0, 'delivery_gross_cents' => 0, 'is_public' => true, 'content' => ['included' => 'L’emplacement', 'delivery_reference' => 'Justification coût déjà affecté au plan']], $extra));
    }

    private function sign(EventOffer $offer, array $extra = []): EventBooking
    {
        return app(ConditionalBookings::class)->sign($offer, array_replace(['buyer_name' => 'Client de test', 'buyer_email' => 'client@example.test',
            'quantity' => 1, 'agreement_reference' => 'accord:test', 'idempotency_key' => (string) Str::uuid()], $extra));
    }

    private function admin(): Admin
    {
        config()->set('qapas_application.admin_enabled', true);
        $admin = Admin::create(['name' => 'Admin test', 'email' => 'admin@example.test', 'password' => 'test-only-password', 'is_active' => true]);
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_fresh_database_has_no_project_data_without_the_explicit_demo_flag(): void
    {
        $this->seed(FarmersGamesDemoSeeder::class);
        $this->assertDatabaseCount('planner_events', 0);
        config()->set('event_planner.demo_enabled', true);
        app()->instance('env', 'production');
        (new FarmersGamesDemoSeeder)->run();
        $this->assertDatabaseCount('planner_events', 0);
    }

    public function test_demo_is_repeatable_preserves_edits_and_does_not_invent_financial_proof(): void
    {
        config()->set('event_planner.demo_enabled', true);
        $this->seed(FarmersGamesDemoSeeder::class);
        $event = PlannerEvent::firstOrFail();
        $event->update(['name' => 'Édition de démonstration modifiée']);
        $before = PlanElement::count();
        $this->seed(FarmersGamesDemoSeeder::class);
        $this->assertDatabaseCount('planner_events', 1);
        $this->assertSame($before, PlanElement::count());
        $this->assertSame('Édition de démonstration modifiée', $event->fresh()->name);
        $this->assertDatabaseCount('event_bookings', 0);
        $this->assertDatabaseCount('event_refunds', 0);
        $s = $event->scenarios()->where('is_base', true)->firstOrFail();
        $finance = app(EventFinance::class)->summarize($s);
        $this->assertFalse($finance['can_confirm']);
        $this->assertSame(0, $finance['acquired_revenue_cents']);
        $this->assertGreaterThan(24, $finance['unknown_costs']);
        $offers = $s->offers()->get()->keyBy('reference_key');
        $this->assertSame($offers['bare-stand']->pool_id, $offers['mounted-stand']->pool_id);
        $this->assertSame(25000, $offers['bare-stand']->price_cents);
    }

    public function test_equipment_keeps_its_dimensions_despite_submitted_free_vertices_and_only_one_cost_identity(): void
    {
        $s = $this->scenario();
        $item = $this->element($s, ['geometry' => ['type' => 'Polygon', 'coordinates' => [[[0, 0], [1, 0], [0, 1], [0, 0]]]]]);
        $measure = app(PlanGeometry::class)->measurements($item->geometry);
        $this->assertEqualsWithDelta(5.76, $measure['area_m2'], 0.01);
        $this->assertEqualsWithDelta(9.6, $measure['length_m'], 0.01);
        app(PlanWriter::class)->save($s, array_merge($item->only(['name', 'category', 'subcategory', 'shape', 'dimensions', 'color']), ['expected_revision' => $item->revision]), $item);
        $this->assertSame(1, $item->budgetLines()->count());
        $this->assertSame(2, $item->fresh()->revision);
    }

    public function test_stale_edits_do_not_overwrite_another_planners_changes(): void
    {
        $s = $this->scenario();
        $item = $this->element($s);
        $old = $item->revision;
        app(PlanWriter::class)->save($s, array_merge($item->only(['name', 'category', 'subcategory', 'shape', 'dimensions', 'color']), ['name' => 'Nouveau nom', 'expected_revision' => $old]), $item);
        try {
            app(PlanWriter::class)->save($s, array_merge($item->only(['name', 'category', 'subcategory', 'shape', 'dimensions', 'color']), ['name' => 'Nom ancien', 'expected_revision' => $old]), $item);
            $this->fail();
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
        $this->assertSame('Nouveau nom', $item->fresh()->name);
    }

    public function test_irregular_spaces_and_holes_are_valid_but_crossings_and_external_holes_are_rejected(): void
    {
        $geo = app(PlanGeometry::class);
        $outer = [[0, 0], [.001, 0], [.001, .001], [.0003, .002], [0, .001], [0, 0]];
        $hole = [[.0001, .0001], [.0002, .0001], [.0002, .0002], [.0001, .0002], [.0001, .0001]];
        $valid = $geo->make('free_polygon', [], ['type' => 'Polygon', 'coordinates' => [$outer, $hole]]);
        $this->assertGreaterThan(0, $geo->measurements($valid)['area_m2']);
        foreach ([[[0, 0], [1, 1], [0, 1], [1, 0], [0, 0]], [[0, 0], [1, 0], [.5, 0], [1, 1], [0, 0]]] as $ring) {
            try {
                $geo->make('free_polygon', [], ['type' => 'Polygon', 'coordinates' => [$ring]]);
                $this->fail('Self crossing must fail.');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $this->expectException(ValidationException::class);
        $geo->make('free_polygon', [], ['type' => 'Polygon', 'coordinates' => [$outer, [[1, 1], [2, 1], [2, 2], [1, 1]]]]);
    }

    public function test_workforce_overlap_is_checked_by_the_writer_used_by_both_ui_and_api(): void
    {
        $s = $this->scenario();
        $ops = ['person_reference' => 'person:test', 'shift_start' => '2026-12-12 10:00', 'shift_end' => '2026-12-12 12:00'];
        $this->element($s, ['operations' => $ops]);
        $this->element($s, ['operations' => array_replace($ops, ['shift_start' => '2026-12-12 12:00', 'shift_end' => '2026-12-12 14:00'])]);
        $this->expectException(ValidationException::class);
        $this->element($s, ['operations' => array_replace($ops, ['shift_start' => '2026-12-12 11:00'])]);
    }

    public function test_the_first_signature_fixes_a_single_21_day_deadline_and_later_orders_keep_their_price(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1)->setTime(12, 0));
        $s = $this->scenario();
        $offer = $this->offer($s);
        $first = $this->sign($offer);
        $deadline = $s->event->fresh()->decision_deadline;
        $this->assertSame('2026-10-22 12:00:00', $deadline->format('Y-m-d H:i:s'));
        $this->travel(4)->days();
        $offer->update(['price_cents' => 50000, 'price_version' => 2]);
        $second = $this->sign($offer);
        $this->assertSame($first->deadline->toIso8601String(), $second->deadline->toIso8601String());
        $this->assertSame(25000, $first->fresh()->gross_cents);
        $this->assertSame(50000, $second->gross_cents);
        $this->assertSame(1, $first->fresh()->price_version);
        $this->assertSame(2, $second->price_version);
    }

    public function test_signature_and_payment_retries_are_idempotent_and_shared_variants_cannot_oversell(): void
    {
        $s = $this->scenario();
        $pool = $s->pools()->create(['name' => 'Un seul emplacement', 'capacity' => 1]);
        $bare = $this->offer($s, ['pool_id' => $pool->id]);
        $mounted = $this->offer($s, ['pool_id' => $pool->id, 'price_cents' => 50000]);
        $first = $this->sign($bare, ['idempotency_key' => 'retry:test']);
        $same = $this->sign($bare, ['idempotency_key' => 'retry:test']);
        $this->assertSame($first->id, $same->id);
        $service = app(ConditionalBookings::class);
        $service->recordPayment($first, 'bank:test:1');
        $service->recordPayment($first, 'bank:test:1');
        $this->assertSame(1, $pool->fresh()->reserved);
        $this->assertSame(0, $mounted->fresh()->available());
        $second = $this->sign($mounted);
        try {
            $service->recordPayment($second, 'bank:test:2');
            $this->fail();
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
        $this->assertSame('signed', $second->fresh()->state);
        $this->assertNull($second->fresh()->paid_at);
        $this->assertSame(1, $s->budgetLines()->where('reference_key', 'like', 'delivery:%')->count());
    }

    public function test_deadline_expiry_closes_offers_reserves_full_refund_and_never_marks_an_unexecuted_refund_complete(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1)->setTime(12, 0));
        $s = $this->scenario();
        $o = $this->offer($s);
        $booking = $this->sign($o);
        $service = app(ConditionalBookings::class);
        $service->recordPayment($booking, 'bank:test');
        app(PublicPlan::class)->publish($s);
        $this->travel(21)->days();
        $this->getJson('/visites/'.$s->event->slug.'/plan.json')->assertOk()->assertJsonPath('event.decision', 'not_confirmed')->assertJsonPath('offers.0.available', false);
        $this->artisan('events:expire-preorders')->assertExitCode(0);
        $this->artisan('events:expire-preorders')->assertExitCode(0);
        $this->assertSame('not_confirmed', $s->event->fresh()->decision);
        $refund = EventRefund::firstOrFail();
        $this->assertSame(25000, $refund->amount_cents);
        $this->assertSame('requested', $refund->state);
        $this->assertSame(25000, app(EventFinance::class)->summarize($s)['reserved_cash_cents']);
        $service->recordRefund($refund, 'refund:proof');
        $this->assertSame('submitted', $refund->fresh()->state);
        $this->assertSame('refund_pending', $booking->fresh()->state);
        $service->recordRefund($refund, 'refund:proof', true);
        $service->recordRefund($refund, 'refund:proof', true);
        $this->assertSame('refunded', $booking->fresh()->state);
        $this->assertDatabaseCount('event_refunds', 1);
        $this->assertSame(0, app(EventFinance::class)->summarize($s)['reserved_cash_cents']);
    }

    public function test_expected_revenue_and_own_cash_are_not_acquired_sales_and_unknown_costs_block_confirmation(): void
    {
        $s = $this->scenario(true);
        $this->element($s);
        $this->offer($s, ['content' => ['target_quantity' => 100]]);
        $s->update(['own_cash_cents' => 10000000]);
        $finance = app(EventFinance::class)->summarize($s);
        $this->assertSame(0, $finance['acquired_revenue_cents']);
        $this->assertSame(2000000, $finance['forecast_revenue_cents']);
        $this->assertSame(1, $finance['unknown_costs']);
        $this->assertFalse($finance['can_confirm']);
        $this->expectException(ValidationException::class);
        app(ConditionalBookings::class)->confirm($s);
    }

    public function test_known_paid_sales_and_a_documented_base_allow_confirmation_and_one_official_publication(): void
    {
        $s = $this->scenario(true);
        $element = $this->element($s);
        $element->budgetLines()->first()->update(['gross_cents' => 12300, 'net_cents' => 10000, 'source_reference' => 'quote:test']);
        $o = $this->offer($s, ['element_id' => $element->id, 'delivery_cost_cents' => 500, 'delivery_gross_cents' => 615]);
        $service = app(ConditionalBookings::class);
        $booking = $this->sign($o);
        $service->recordPayment($booking, 'bank:test');
        $finance = app(EventFinance::class)->summarize($s);
        $this->assertSame(10500, $finance['operating_cents']);
        $this->assertSame(20000, $finance['acquired_revenue_cents']);
        $this->assertSame(25000, $finance['reserved_cash_cents']);
        $this->assertTrue($finance['can_confirm']);
        $service->confirm($s);
        $service->confirm($s);
        $this->assertSame('confirmed', $s->event->fresh()->decision);
        $this->assertSame('confirmed', $booking->fresh()->state);
        $this->assertDatabaseCount('event_publications', 1);
        $this->get('/visites/'.$s->event->slug)->assertOk()->assertSee('Événement confirmé');
        $this->get('/visites/'.$s->event->slug.'/poster')->assertOk()->assertSee('Événement confirmé');
        $this->assertSame(0, app(EventFinance::class)->summarize($s)['reserved_cash_cents']);
    }

    public function test_cancelled_forecasts_do_not_block_base_but_earmarked_cash_is_not_free_cash(): void
    {
        $s = $this->scenario(true);
        $s->budgetLines()->create(['label' => 'Retiré', 'kind' => 'cost', 'status' => 'cancelled']);
        $s->budgetLines()->create(['label' => 'Budget', 'kind' => 'cost', 'gross_cents' => 12000, 'net_cents' => 10000, 'source_reference' => 'quote']);
        $s->budgetLines()->create(['label' => 'Aide réservée', 'kind' => 'revenue', 'status' => 'paid', 'gross_cents' => 100000, 'net_cents' => 100000, 'source_reference' => 'aide', 'earmarked' => true]);
        $finance = app(EventFinance::class)->summarize($s);
        $this->assertSame(0, $finance['unknown_costs']);
        $this->assertSame(0, $finance['acquired_revenue_cents']);
        $this->assertSame(0, $finance['available_cash_cents']);
        $this->assertFalse($finance['can_confirm']);
    }

    public function test_committed_budget_values_and_paid_status_cannot_be_rewritten(): void
    {
        $s = $this->scenario();
        $writer = app(BudgetWriter::class);
        $data = ['label' => 'Fournisseur', 'kind' => 'cost', 'cost_class' => 'operating', 'status' => 'paid', 'gross_cents' => 12000, 'net_cents' => 10000, 'source_reference' => 'invoice:test', 'element_id' => null, 'essential' => true, 'earmarked' => false];
        $line = $writer->save($s, $data);
        foreach ([['gross_cents' => 0], ['status' => 'forecast']] as $change) {
            try {
                $writer->save($s, array_replace($data, $change), $line->id);
                $this->fail();
            } catch (HttpException $e) {
                $this->assertSame(422, $e->getStatusCode());
            }
        }
        $this->assertSame(12000, $line->fresh()->gross_cents);
        $this->assertSame('paid', $line->fresh()->status);
    }

    public function test_public_snapshot_stays_immutable_and_never_contains_costs_people_or_customer_information(): void
    {
        $s = $this->scenario();
        $item = $this->element($s, ['operations' => ['person_reference' => 'SECRET-PERSON'], 'four_ps' => ['price' => 'SECRET-MARGIN']]);
        $o = $this->offer($s);
        $booking = $this->sign($o);
        app(ConditionalBookings::class)->recordPayment($booking, 'SECRET-BANK');
        $p = app(PublicPlan::class)->publish($s);
        $item->update(['name' => 'Nom du brouillon']);
        $response = $this->getJson('/visites/'.$s->event->slug.'/plan.json')->assertOk()->assertJsonPath('plan.features.0.properties.name', 'Stand');
        foreach (['SECRET-PERSON', 'SECRET-MARGIN', 'SECRET-BANK', 'client@example.test', 'net_cents', 'budget_lines', 'buyer_name', 'operations'] as $secret) {
            $this->assertStringNotContainsString($secret, $response->getContent());
        }
        $this->assertStringNotContainsString('client@example.test', DB::table('event_bookings')->value('buyer_email'));
        $this->expectException(\LogicException::class);
        $p->update(['snapshot' => []]);
    }

    public function test_demo_publication_is_hidden_on_a_public_host_or_when_demo_is_disabled(): void
    {
        config()->set('event_planner.demo_enabled', true);
        $this->seed(FarmersGamesDemoSeeder::class);
        $slug = PlannerEvent::firstOrFail()->slug;
        $this->get('http://127.0.0.1/visites/'.$slug)->assertOk();
        $this->get('https://events.example.test/visites/'.$slug.'/plan.json')->assertNotFound();
        config()->set('event_planner.demo_enabled', false);
        $this->get('http://127.0.0.1/visites/'.$slug.'/poster')->assertNotFound();
    }

    public function test_private_map_requires_active_admin_authority_and_rechecks_it_for_mutations(): void
    {
        $s = $this->scenario();
        $this->getJson('/planner/scenarios/'.$s->uuid.'/plan.json')->assertForbidden();
        $admin = $this->admin();
        $this->getJson('/planner/scenarios/'.$s->uuid.'/plan.json')->assertOk();
        $admin->update(['is_active' => false]);
        $this->postJson('/planner/scenarios/'.$s->uuid.'/elements', [])->assertForbidden();
    }

    public function test_all_six_modules_render_with_the_demo_project_and_expose_their_workflows(): void
    {
        $this->admin();
        config()->set('event_planner.demo_enabled', true);
        $this->seed(FarmersGamesDemoSeeder::class);
        foreach ([EventPlan::class, EventCosts::class, EventRevenues::class, EventBalance::class, EventCommunications::class, EventProducts::class] as $page) {
            Livewire::test($page)->assertSuccessful()->assertSee('Démonstration');
        }
    }

    public function test_scenario_copy_keeps_hypotheses_but_no_cash_customer_commitments_staff_assignment_or_stock(): void
    {
        $s = $this->scenario(true);
        $element = $this->element($s, ['operations' => ['person_reference' => 'PERSON', 'shift_start' => '2026-12-12 09:00', 'shift_end' => '2026-12-12 10:00']]);
        $offer = $this->offer($s, ['element_id' => $element->id]);
        $b = $this->sign($offer);
        app(ConditionalBookings::class)->recordPayment($b, 'bank');
        $product = $s->products()->create(['sku' => 'TEST', 'name' => 'T-shirt', 'price_cents' => 1000]);
        app(StockLedger::class)->move($product, 'receipt', 10, 'receipt');
        $clone = app(ScenarioCloner::class)->copy($s, 'Variante');
        $this->assertFalse($clone->is_base);
        $this->assertSame(0, $clone->own_cash_cents);
        $this->assertNull($clone->readiness_reference);
        $this->assertNull($clone->elements()->first()->operations['person_reference'] ?? null);
        $this->assertNotSame($element->uuid, $clone->elements()->first()->uuid);
        $this->assertSame(0, $clone->products()->first()->stock);
        $this->assertSame(0, app(EventFinance::class)->summarize($clone)['acquired_revenue_cents']);
        $this->assertDatabaseCount('event_bookings', 1);
    }

    public function test_stock_transfers_and_sales_reconcile_without_creating_revenue_for_independent_vendors(): void
    {
        $s = $this->scenario();
        $stand = $this->element($s);
        $ledger = app(StockLedger::class);
        $product = $s->products()->create(['sku' => 'SHIRT', 'name' => 'T-shirt', 'price_cents' => 2000, 'net_price_cents' => 1600, 'unit_cost_cents' => 500]);
        $ledger->move($product, 'receipt', 10, 'receipt');
        $ledger->move($product, 'transfer', 4, 'transfer', $stand->id);
        $ledger->move($product, 'sale', 2, 'sale', $stand->id);
        $ledger->move($product, 'sale', 2, 'sale', $stand->id);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame(2, $product->locations()->where('element_id', $stand->id)->value('quantity'));
        $this->assertSame(6, $product->locations()->where('location_key', 'depot')->value('quantity'));
        $this->assertSame(3200, $s->budgetLines()->where('kind', 'revenue')->sum('net_cents'));
        $this->assertSame(1000, $s->budgetLines()->where('reference_key', 'like', 'consumption:%')->sum('net_cents'));
        $independent = $s->products()->create(['sku' => 'OTHER', 'name' => 'Produit exposant', 'seller' => 'independent', 'price_cents' => 1000, 'net_price_cents' => 800]);
        $ledger->move($independent, 'receipt', 2, 'other-receipt');
        $ledger->move($independent, 'sale', 1, 'other-sale');
        $this->assertSame(1, $s->budgetLines()->where('kind', 'revenue')->count());
        try {
            $ledger->move($product, 'sale', 3, 'oversell', $stand->id);
            $this->fail();
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_v2_public_routes_coexist_with_the_existing_events_application(): void
    {
        $this->seed();
        $this->get('/')->assertOk()->assertSee('/visites');
        $this->get('/events/os-jogos-do-agricultor')->assertOk();
        $this->get('/visites')->assertOk();
        $this->assertSame('events', config('qapas_application.id'));
    }

    public function test_print_exports_use_the_same_safe_publication_and_qr_points_to_the_visit(): void
    {
        $s = $this->scenario();
        $this->element($s, ['operations' => ['person_reference' => 'SECRET-PERSON']]);
        app(PublicPlan::class)->publish($s);
        $base = '/visites/'.$s->event->slug;
        $svg = $this->get($base.'/plan.svg')->assertOk()->assertHeader('Content-Type', 'image/svg+xml')->getContent();
        $this->assertStringContainsString('Stand', $svg);
        $this->assertStringNotContainsString('SECRET-PERSON', $svg);
        $pdf = $this->get($base.'/plan.pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf')->getContent();
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('Stand', $pdf);
        $this->assertStringNotContainsString('SECRET-PERSON', $pdf);
        $this->get($base.'/poster')->assertOk()->assertSee('data:image/svg+xml;base64', false)->assertSee($base);
    }

    public function test_teaser_and_unpublished_price_changes_do_not_open_the_candidate_funnel(): void
    {
        $s = $this->scenario();
        $offer = $this->offer($s);
        $event = $s->event;
        $event->update(['phase' => 'teaser']);
        app(PublicPlan::class)->publish($s->refresh());
        $base = '/visites/'.$event->slug;
        $this->getJson($base.'/plan.json')->assertJsonPath('offers.0.available', false);
        $event->update(['phase' => 'applications']);
        app(PublicPlan::class)->publish($s->refresh());
        $this->getJson($base.'/plan.json')->assertJsonPath('offers.0.available', true);
        $offer->update(['price_cents' => 50000, 'price_version' => 2]);
        $this->getJson($base.'/plan.json')->assertJsonPath('offers.0.available', false)->assertJsonPath('offers.0.price_cents', 25000);
        $this->get($base.'/offers/'.$offer->uuid.'/candidate')->assertNotFound();
    }

    public function test_zero_is_an_observed_result_and_offer_reviews_are_linked_to_the_selected_offer(): void
    {
        $this->admin();
        $s = $this->scenario();
        $offer = $this->offer($s);
        Livewire::test(EventCommunications::class)->set('review.name', 'Conversion mesurée')->set('review.level', 'offer')
            ->set('review.offer_id', $offer->id)->set('review.evidence_kind', 'observed')->set('review.target', '1')->set('review.observed', '0')
            ->set('review.evidence_reference', 'Source test datée : 01/10/2026')->call('saveReview')->assertHasNoErrors();
        $this->assertDatabaseHas('event_four_p_reviews', ['offer_id' => $offer->id, 'observed' => '0', 'target' => '1', 'evidence_kind' => 'observed']);
    }

    public function test_an_equipment_category_cannot_bypass_its_regular_shape_through_the_api(): void
    {
        $s = $this->scenario();
        $this->admin();
        $this->postJson('/planner/scenarios/'.$s->uuid.'/elements', ['name' => 'Stand', 'category' => 'structures', 'subcategory' => 'independent_stand', 'shape' => 'free_polygon',
            'color' => '#217846', 'geometry' => ['type' => 'Polygon', 'coordinates' => [[[0, 0], [1, 0], [1, 1], [0, 0]]]]])->assertUnprocessable();
        $this->assertDatabaseCount('plan_elements', 0);
    }

    public function test_v2_access_uses_the_events_admin_guard_and_rechecks_deactivation(): void
    {
        $admin = $this->admin();
        $this->assertTrue(EventAccess::canManage());
        $admin->is_active = false;
        $this->assertFalse(EventAccess::canManage());
        $this->assertFalse(auth('web')->check());
    }

    public function test_v2_public_proposal_cannot_bypass_missing_verified_identity(): void
    {
        $s = $this->scenario();
        $offer = $this->offer($s);
        app(PublicPlan::class)->publish($s);
        $url = '/visites/'.$s->event->slug.'/offers/'.$offer->uuid.'/candidate';
        $this->get($url)->assertOk()->assertSee('identité vérifiée');
        $this->post($url, ['proposal' => 'Proposition de test', 'acknowledged' => '1'])->assertForbidden();
        $this->assertDatabaseCount('event_leads', 0);
        $this->assertDatabaseCount('event_bookings', 0);
    }

    public function test_decimal_money_is_exact_and_rejects_rounding_or_negative_inputs(): void
    {
        $this->assertSame(4999, Money::parse('49,99'));
        $this->assertSame(0, Money::parse('0'));
        $this->assertNull(Money::parse(''));
        $this->expectException(ValidationException::class);
        Money::parse('49.999');
    }
}
