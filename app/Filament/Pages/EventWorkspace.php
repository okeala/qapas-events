<?php

namespace App\Filament\Pages;

use App\Models\EventOffer;
use App\Models\EventRefund;
use App\Models\EventScenario;
use App\Models\FourPReview;
use App\Models\PlanElement;
use App\Models\PlannerEvent;
use App\Services\Events\BudgetWriter;
use App\Services\Events\ConditionalBookings;
use App\Services\Events\EventAccess;
use App\Services\Events\EventFinance;
use App\Services\Events\Money;
use App\Services\Events\PlanWriter;
use App\Services\Events\PublicPlan;
use App\Services\Events\ScenarioCloner;
use App\Services\Events\StockLedger;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;

abstract class EventWorkspace extends Page
{
    protected string $view = 'filament.pages.event-workspace';

    protected string $module = 'plan';

    #[Url(as: 'event')]
    public ?int $eventId = null;

    #[Url(as: 'scenario')]
    public ?int $scenarioId = null;

    public ?int $elementId = null;

    public array $eventForm = [];

    public array $plan = [];

    public array $cost = [];

    public array $offer = [];

    public array $review = [];

    public array $campaign = [];

    public array $lead = [];

    public array $product = [];

    public array $booking = [];

    public array $movement = [];

    public array $readiness = [];

    public array $editorial = [];

    public static function canAccess(): bool
    {
        return EventAccess::canManage();
    }

    public function mount(): void
    {
        EventAccess::authorize();
        $this->eventId ??= session('planner_event_id') ?? $this->events()->value('id');
        $this->scenarioId ??= session('planner_scenario_id');
        $this->selectEvent();
        $this->newElement();
    }

    private function events()
    {
        return PlannerEvent::query()->when(! app()->environment('local', 'testing') || ! config('event_planner.demo_enabled'), fn ($q) => $q->where('is_demo', false));
    }

    public function selectedEvent(): ?PlannerEvent
    {
        EventAccess::authorize();

        return $this->eventId ? $this->events()->findOrFail($this->eventId) : null;
    }

    public function selectedScenario(): ?EventScenario
    {
        $event = $this->selectedEvent();

        return $event && $this->scenarioId ? $event->scenarios()->findOrFail($this->scenarioId) : null;
    }

    public function selectEvent(): void
    {
        $event = $this->selectedEvent();
        if ($event && ! $event->scenarios()->whereKey($this->scenarioId)->exists()) {
            $this->scenarioId = $event->scenarios()->orderByDesc('is_base')->value('id');
        }
        if (! $event) {
            $this->scenarioId = null;
        }
        session(['planner_event_id' => $this->eventId, 'planner_scenario_id' => $this->scenarioId]);
        $this->elementId = null;
        $this->resetForms();
        $this->loadReadiness();
    }

    public function selectScenario(): void
    {
        $this->selectedScenario();
        session(['planner_scenario_id' => $this->scenarioId]);
        $this->elementId = null;
        $this->resetForms();
        $this->loadReadiness();
        $this->newElement();
    }

    private function resetForms(): void
    {
        $this->cost = ['id' => null, 'label' => '', 'kind' => $this->module === 'revenues' ? 'revenue' : 'cost', 'cost_class' => 'operating', 'status' => 'forecast', 'gross' => '', 'net' => '', 'source_reference' => '', 'element_id' => '', 'essential' => true, 'earmarked' => false];
        $this->offer = ['id' => null, 'name' => '', 'audience' => 'exhibitors', 'seller' => 'organizer', 'price' => '', 'net_price' => '', 'delivery_cost' => '', 'delivery_gross' => '', 'delivery_reference' => '', 'element_id' => '', 'pool_id' => '', 'capacity' => '', 'description' => '', 'included' => '', 'conditions' => '', 'is_public' => false, 'four_ps' => ['product' => '', 'price' => '', 'place' => '', 'promotion' => '']];
        $this->review = ['level' => 'event', 'element_id' => '', 'offer_id' => '', 'audience' => 'visitors', 'name' => '', 'product' => '', 'price' => '', 'place' => '', 'promotion' => '', 'hypothesis' => '', 'indicator' => '', 'unit' => '', 'target' => '', 'observed' => '', 'evidence_kind' => 'hypothesis', 'evidence_reference' => '', 'owner' => '', 'due_at' => '', 'next_action' => '', 'decision' => 'improve'];
        $this->campaign = ['id' => null, 'name' => '', 'audience' => 'visitors', 'channel' => 'local', 'phase' => $this->selectedEvent()?->phase ?? 'teaser', 'message' => '', 'call_to_action' => 'Découvrir', 'budget' => '0', 'owner' => '', 'due_at' => '', 'delivery_reference' => '', 'state' => 'draft'];
        $this->lead = ['id' => null, 'name' => '', 'email' => '', 'audience' => 'exhibitors', 'source' => '', 'stage' => 'contact', 'owner' => '', 'next_action' => '', 'due_at' => '', 'notes' => ''];
        $this->product = ['id' => null, 'sku' => '', 'name' => '', 'seller' => 'organizer', 'price' => '', 'net_price' => '', 'unit_cost' => '', 'element_id' => '', 'is_public' => false, 'description' => ''];
        $this->booking = ['offer_id' => '', 'buyer_name' => '', 'buyer_email' => '', 'quantity' => 1, 'agreement_reference' => '', 'idempotency_key' => (string) Str::uuid()];
        $this->movement = ['product_id' => '', 'kind' => 'receipt', 'quantity' => 1, 'reference' => '', 'element_id' => '', 'source_element_id' => '', 'reason' => ''];
        $this->eventForm = ['name' => '', 'slug' => '', 'venue' => '', 'lat' => '0', 'lng' => '0', 'decision_days' => 21, 'entry_free' => false, 'starts_at' => '', 'ends_at' => '', 'description' => ''];
        $this->loadEditorial();
    }

    private function loadReadiness(): void
    {
        $s = $this->selectedScenario();
        $this->readiness = ['prerequisites' => $s?->prerequisites ?? array_fill_keys(array_keys(config('event_planner.prerequisites')), false),
            'reference' => $s?->readiness_reference ?? '', 'contingency' => Money::input($s?->contingency_cents), 'target_profit' => Money::input($s?->target_profit_cents),
            'own_cash' => Money::input($s?->own_cash_cents), 'profit_required' => $s?->profit_required ?? false];
    }

    public function createEvent(): void
    {
        EventAccess::authorize();
        $this->validate(['eventForm.name' => 'required|string|max:160', 'eventForm.slug' => 'required|alpha_dash|max:160|unique:planner_events,slug',
            'eventForm.venue' => 'nullable|string|max:200', 'eventForm.lat' => 'required|numeric|between:-85,85', 'eventForm.lng' => 'required|numeric|between:-180,180',
            'eventForm.decision_days' => 'required|integer|between:1,21', 'eventForm.starts_at' => 'nullable|date', 'eventForm.ends_at' => 'nullable|date|after_or_equal:eventForm.starts_at',
            'eventForm.description' => 'nullable|string|max:10000', 'eventForm.entry_free' => 'boolean']);
        DB::transaction(function () {
            $e = PlannerEvent::create(['name' => $this->eventForm['name'], 'slug' => $this->eventForm['slug'], 'venue' => $this->eventForm['venue'],
                'decision_days' => $this->eventForm['decision_days'], 'starts_at' => $this->eventForm['starts_at'] ?: null, 'ends_at' => $this->eventForm['ends_at'] ?: null,
                'description' => [app()->getLocale() => $this->eventForm['description']], 'settings' => ['entry_free' => $this->eventForm['entry_free']]]);
            $s = $e->scenarios()->create(['name' => 'Format de base', 'is_base' => true, 'center_lat' => $this->eventForm['lat'], 'center_lng' => $this->eventForm['lng'],
                'prerequisites' => array_fill_keys(array_keys(config('event_planner.prerequisites')), false)]);
            $this->eventId = $e->id;
            $this->scenarioId = $s->id;
        });
        $this->selectEvent();
        $this->newElement();
        $this->notice('Événement créé.');
    }

    public function createScenario(string $name): void
    {
        EventAccess::authorize();
        validator(compact('name'), ['name' => 'required|string|max:120'])->validate();
        $current = $this->selectedScenario();
        $scenario = $this->selectedEvent()->scenarios()->create(['name' => $name, 'center_lat' => $current?->center_lat ?? 0, 'center_lng' => $current?->center_lng ?? 0, 'prerequisites' => []]);
        $this->scenarioId = $scenario->id;
        $this->selectScenario();
        $this->notice('Scénario vide créé.');
    }

    public function copyScenario(string $name): void
    {
        $this->scenarioId = app(ScenarioCloner::class)->copy($this->selectedScenario(), $name)->id;
        $this->selectScenario();
        $this->notice('Variante créée ; engagements, encaissements et personnes ne sont pas recopiés.');
    }

    public function setBaseScenario(): void
    {
        $scenario = $this->selectedScenario();
        $event = $this->selectedEvent();
        DB::transaction(function () use ($scenario, $event) {
            $event = PlannerEvent::query()->lockForUpdate()->findOrFail($event->id);
            abort_unless($event->decision === 'pending', 422, 'Le format confirmé est conservé.');
            $event->scenarios()->update(['is_base' => false]);
            $scenario->update(['is_base' => true]);
        });
        $this->notice('Ce scénario est désormais le format de base.');
    }

    public function loadEditorial(): void
    {
        $locale = $this->editorial['locale'] ?? 'fr';
        abort_unless(in_array($locale, config('qapas_application.locales'), true), 422);
        $event = $this->selectedEvent();
        $settings = $event?->settings ?? [];
        $copy = [];
        foreach (config('event_planner.phases') as $key => $label) {
            $copy[$key] = ['title' => $settings['phase_copy'][$key][$locale]['title'] ?? '', 'message' => $settings['phase_copy'][$key][$locale]['message'] ?? ''];
        }
        $faq = [];
        foreach ($settings['public_faq'] ?? [] as $item) {
            $faq[] = ['question' => $item['question'][$locale] ?? '', 'answer' => $item['answer'][$locale] ?? ''];
        }
        $this->editorial = ['locale' => $locale, 'description' => $event?->description[$locale] ?? '', 'phases' => $copy, 'faq' => $faq];
    }

    public function addFaq(): void
    {
        $this->selectedEvent();
        $this->editorial['faq'][] = ['question' => '', 'answer' => ''];
    }

    public function saveEditorial(): void
    {
        $event = $this->selectedEvent();
        abort_unless($event, 422);
        $this->validate(['editorial.locale' => ['required', Rule::in(config('qapas_application.locales'))],
            'editorial.description' => 'nullable|string|max:10000', 'editorial.phases' => 'array', 'editorial.phases.*.title' => 'nullable|string|max:200',
            'editorial.phases.*.message' => 'nullable|string|max:10000', 'editorial.faq' => 'array|max:100', 'editorial.faq.*.question' => 'nullable|string|max:1000',
            'editorial.faq.*.answer' => 'nullable|string|max:10000']);
        DB::transaction(function () use ($event) {
            $event = PlannerEvent::query()->lockForUpdate()->findOrFail($event->id);
            $locale = $this->editorial['locale'];
            $settings = $event->settings ?? [];
            $description = $event->description ?? [];
            $description[$locale] = $this->editorial['description'];
            foreach (config('event_planner.phases') as $key => $label) {
                $settings['phase_copy'][$key][$locale] = $this->editorial['phases'][$key];
            }
            foreach ($this->editorial['faq'] as $i => $item) {
                abort_if(($item['question'] === '') !== ($item['answer'] === ''), 422, 'Une FAQ exige une question et sa réponse.');
                $settings['public_faq'][$i]['question'][$locale] = $item['question'];
                $settings['public_faq'][$i]['answer'][$locale] = $item['answer'];
            }
            $event->update(['settings' => $settings, 'description' => $description]);
        });
        $this->notice('Textes du brouillon enregistrés. Publier le plan pour actualiser la visite et l’affiche.');
    }

    public function newElement(): void
    {
        EventAccess::authorize();
        $s = $this->selectedScenario();
        $this->elementId = null;
        $this->plan = ['name' => '', 'category' => 'structures', 'subcategory' => 'independent_stand', 'shape' => 'square', 'color' => '#258052',
            'dimensions' => ['lat' => (float) ($s?->center_lat ?? 0), 'lng' => (float) ($s?->center_lng ?? 0), 'width' => 2.4, 'height' => 2.4, 'radius' => 1.2, 'sides' => 6, 'angle' => 0],
            'geometry' => null, 'is_public' => false, 'is_essential' => false, 'tour_order' => null, 'expected_revision' => 0,
            'four_ps' => ['product' => '', 'price' => '', 'place' => '', 'promotion' => ''], 'success' => ['objective' => '', 'target' => '', 'observed' => '', 'evidence' => ''],
            'public_content' => ['description' => '', 'public_price' => '', 'hours' => '', 'access' => '', 'story' => ''], 'operations' => ['organization' => 'QAPAS', 'responsible' => '', 'resource_reference' => '', 'notes' => '', 'person_reference' => '', 'shift_start' => '', 'shift_end' => '', 'status' => 'needed']];
    }

    public function chooseCategory(string $category): void
    {
        EventAccess::authorize();
        $catalog = config('event_planner.categories.'.$category);
        abort_unless($catalog, 422);
        $this->newElement();
        $this->plan['category'] = $category;
        $this->plan['subcategory'] = array_key_first($catalog['items']);
        $this->plan['shape'] = $catalog['shape'];
        $this->plan['color'] = $catalog['color'];
    }

    public function updatedPlanCategory(string $category): void
    {
        $catalog = config('event_planner.categories.'.$category);
        abort_unless($catalog, 422);
        $this->plan['subcategory'] = array_key_first($catalog['items']);
    }

    public function selectElement(int $id): void
    {
        $item = $this->selectedScenario()->elements()->whereNull('archived_at')->findOrFail($id);
        $this->newElement();
        $this->elementId = $item->id;
        $this->plan = array_replace_recursive($this->plan, $item->only(['name', 'category', 'subcategory', 'shape', 'color', 'dimensions', 'geometry', 'is_public', 'is_essential', 'tour_order', 'four_ps', 'success', 'public_content', 'operations']));
        $this->plan['expected_revision'] = $item->revision;
    }

    public function savePlan(): void
    {
        EventAccess::authorize();
        $s = $this->selectedScenario();
        abort_unless($s, 422);
        $ops = $this->plan['operations'] ?? [];
        if (! empty($ops['shift_start']) || ! empty($ops['shift_end'])) {
            validator($ops, ['shift_start' => 'required|date', 'shift_end' => 'required|date|after:shift_start'])->validate();
        }
        if (! empty($ops['person_reference']) && ! empty($ops['shift_start'])) {
            foreach ($s->elements()->whereNull('archived_at')->where('id', '!=', $this->elementId ?? 0)->get() as $other) {
                $shift = $other->operations ?? [];
                if (($shift['person_reference'] ?? null) === $ops['person_reference'] && ! empty($shift['shift_start']) && ! empty($shift['shift_end']) && strtotime($ops['shift_start']) < strtotime($shift['shift_end']) && strtotime($ops['shift_end']) > strtotime($shift['shift_start'])) {
                    throw ValidationException::withMessages(['plan.operations.person_reference' => 'Cette personne est déjà affectée sur un créneau chevauchant.']);
                }
            }
        }
        $item = app(PlanWriter::class)->save($s, $this->plan, $this->elementId ? $s->elements()->findOrFail($this->elementId) : null);
        $this->selectElement($item->id);
        $this->dispatch('planner-updated');
        $this->notice('Élément et dossier de coûts enregistrés.');
    }

    public function archiveElement(): void
    {
        EventAccess::authorize();
        app(PlanWriter::class)->archive($this->selectedScenario()->elements()->findOrFail($this->elementId));
        $this->newElement();
        $this->dispatch('planner-updated');
        $this->notice('Élément retiré du brouillon ; historique conservé.');
    }

    public function duplicateElement(): void
    {
        EventAccess::authorize();
        $item = app(PlanWriter::class)->duplicate($this->selectedScenario()->elements()->findOrFail($this->elementId));
        $this->selectElement($item->id);
        $this->dispatch('planner-updated');
        $this->notice('Copie créée avec un nouveau dossier de coûts.');
    }

    public function editCost(int $id): void
    {
        $line = $this->selectedScenario()->budgetLines()->findOrFail($id);
        $this->cost = $line->only(['id', 'label', 'kind', 'cost_class', 'status', 'source_reference', 'element_id', 'essential', 'earmarked']);
        $this->cost['gross'] = Money::input($line->gross_cents);
        $this->cost['net'] = Money::input($line->net_cents);
    }

    public function saveCost(): void
    {
        EventAccess::authorize();
        $s = $this->selectedScenario();
        $this->validate(['cost.label' => 'required|string|max:200', 'cost.kind' => 'required|in:cost,revenue', 'cost.cost_class' => 'required|in:operating,investment,inventory',
            'cost.status' => 'required|in:forecast,committed,invoiced,paid,cancelled', 'cost.source_reference' => 'nullable|string|max:255', 'cost.essential' => 'boolean', 'cost.earmarked' => 'boolean']);
        $element = $this->scopedElement($this->cost['element_id'] ?? null);
        $values = ['label' => $this->cost['label'], 'kind' => $this->cost['kind'], 'cost_class' => $this->cost['cost_class'], 'status' => $this->cost['status'],
            'gross_cents' => Money::parse($this->cost['gross']), 'net_cents' => Money::parse($this->cost['net']), 'source_reference' => $this->cost['source_reference'] ?: null,
            'element_id' => $element?->id, 'essential' => $this->cost['essential'], 'earmarked' => $this->cost['earmarked']];
        app(BudgetWriter::class)->save($s, $values, $this->cost['id']);
        $this->notice('Ligne enregistrée.');
    }

    public function editOffer(int $id): void
    {
        $o = $this->selectedScenario()->offers()->findOrFail($id);
        $this->offer = array_replace($this->offer, $o->only(['id', 'name', 'audience', 'seller', 'element_id', 'pool_id', 'is_public']));
        $this->offer['four_ps'] = array_replace($this->offer['four_ps'], $o->four_ps ?? []);
        foreach (['price' => 'price_cents', 'net_price' => 'net_price_cents', 'delivery_cost' => 'delivery_cost_cents', 'delivery_gross' => 'delivery_gross_cents'] as $key => $field) {
            $this->offer[$key] = Money::input($o->$field);
        }
        foreach (['description', 'included', 'conditions', 'delivery_reference'] as $key) {
            $this->offer[$key] = $o->content[$key] ?? '';
        }
    }

    public function saveOffer(): void
    {
        EventAccess::authorize();
        $s = $this->selectedScenario();
        $this->validate(['offer.name' => 'required|string|max:200', 'offer.audience' => ['required', Rule::in(array_keys(config('event_planner.audiences')))],
            'offer.seller' => 'required|in:organizer,independent', 'offer.description' => 'nullable|string|max:6000', 'offer.included' => 'nullable|string|max:4000',
            'offer.conditions' => 'nullable|string|max:4000', 'offer.is_public' => 'boolean', 'offer.capacity' => 'nullable|integer|between:1,100000',
            'offer.four_ps' => 'array', 'offer.four_ps.*' => 'nullable|string|max:4000', 'offer.delivery_reference' => 'nullable|string|max:255']);
        $price = Money::parse($this->offer['price']);
        abort_if($price === null, 422, 'Prix requis, y compris zéro pour une offre gratuite.');
        $element = $this->scopedElement($this->offer['element_id'] ?? null);
        DB::transaction(function () use ($s, $price, $element) {
            PlannerEvent::query()->lockForUpdate()->findOrFail($s->event_id);
            $s = EventScenario::query()->lockForUpdate()->findOrFail($s->id);
            $o = $this->offer['id'] ? $s->offers()->lockForUpdate()->findOrFail($this->offer['id']) : new EventOffer(['scenario_id' => $s->id]);
            $pool = null;
            if (! empty($this->offer['pool_id'])) {
                $pool = $s->pools()->findOrFail($this->offer['pool_id']);
            } elseif (! empty($this->offer['capacity'])) {
                $pool = $s->pools()->create(['name' => $this->offer['name'], 'capacity' => $this->offer['capacity']]);
            }
            if ($o->exists && $o->bookings()->exists()) {
                abort_unless($o->pool_id === $pool?->id, 422, 'La capacité d’une offre commandée ne peut être remplacée.');
            }
            $net = Money::parse($this->offer['net_price']);
            if ($o->exists && ($o->price_cents !== $price || $o->net_price_cents !== $net || $o->name !== $this->offer['name'] || $o->seller !== $this->offer['seller'] || $o->element_id !== $element?->id
                || ($o->content['included'] ?? '') !== $this->offer['included'] || ($o->content['conditions'] ?? '') !== $this->offer['conditions'] || ($o->content['description'] ?? '') !== $this->offer['description'])) {
                $o->price_version++;
            }
            $o->fill(['name' => $this->offer['name'], 'audience' => $this->offer['audience'], 'seller' => $this->offer['seller'], 'element_id' => $element?->id, 'pool_id' => $pool?->id,
                'four_ps' => array_intersect_key($this->offer['four_ps'], array_flip(['product', 'price', 'place', 'promotion'])),
                'price_cents' => $price, 'net_price_cents' => $net, 'delivery_cost_cents' => Money::parse($this->offer['delivery_cost']), 'delivery_gross_cents' => Money::parse($this->offer['delivery_gross']), 'is_public' => $this->offer['is_public'],
                'content' => array_merge($o->content ?? [], ['description' => $this->offer['description'], 'included' => $this->offer['included'], 'conditions' => $this->offer['conditions'], 'delivery_reference' => $this->offer['delivery_reference']])])->save();
            $this->offer['id'] = $o->id;
            $this->offer['pool_id'] = $o->pool_id;
        });
        $this->notice('Offre enregistrée ; commandes antérieures conservées.');
    }

    public function signBooking(): void
    {
        EventAccess::authorize();
        $offer = $this->selectedScenario()->offers()->findOrFail($this->booking['offer_id']);
        app(ConditionalBookings::class)->sign($offer, $this->booking);
        $this->booking['idempotency_key'] = (string) Str::uuid();
        $this->notice('Signature enregistrée. Échéance commune fixée ; paiement à vérifier.');
    }

    public function recordPayment(int $id, string $reference): void
    {
        EventAccess::authorize();
        $b = $this->selectedEvent()->bookings()->findOrFail($id);
        app(ConditionalBookings::class)->recordPayment($b, $reference);
        $this->notice('Encaissement vérifié enregistré.');
    }

    public function recordRefund(int $id, string $reference, bool $completed = false): void
    {
        EventAccess::authorize();
        $refund = EventRefund::query()->whereHas('booking', fn ($q) => $q->where('event_id', $this->eventId))->findOrFail($id);
        app(ConditionalBookings::class)->recordRefund($refund, $reference, $completed);
        $this->notice($completed ? 'Restitution vérifiée enregistrée.' : 'Ordre de remboursement enregistré.');
    }

    public function saveReadiness(): void
    {
        EventAccess::authorize();
        $s = $this->selectedScenario();
        $this->validate(['readiness.reference' => 'nullable|string|max:255', 'readiness.prerequisites' => 'array', 'readiness.prerequisites.*' => 'boolean', 'readiness.profit_required' => 'boolean']);
        $s->update(['readiness_reference' => $this->readiness['reference'] ?: null, 'prerequisites' => array_intersect_key($this->readiness['prerequisites'], config('event_planner.prerequisites')),
            'contingency_cents' => Money::parse($this->readiness['contingency']) ?? 0, 'target_profit_cents' => Money::parse($this->readiness['target_profit']) ?? 0,
            'own_cash_cents' => Money::parse($this->readiness['own_cash']) ?? 0, 'profit_required' => $this->readiness['profit_required']]);
        $this->notice('Dossier enregistré.');
    }

    public function changePhase(string $phase): void
    {
        EventAccess::authorize();
        $event = $this->selectedEvent();
        DB::transaction(function () use ($event, $phase) {
            $event = PlannerEvent::query()->lockForUpdate()->findOrFail($event->id);
            abort_unless(in_array($phase, ['teaser', 'applications'], true) && $event->decision === 'pending', 422);
            $event->update(['phase' => $phase]);
        });
        $this->notice('Phase du brouillon actualisée. Publier pour actualiser les supports.');
    }

    public function confirmEvent(): void
    {
        EventAccess::authorize();
        app(ConditionalBookings::class)->confirm($this->selectedScenario());
        $this->notice('Format confirmé et communication officielle publiée.');
    }

    public function rejectEvent(): void
    {
        EventAccess::authorize();
        app(ConditionalBookings::class)->reject($this->selectedEvent());
        $this->notice('Non-confirmation enregistrée ; remboursements à exécuter.');
    }

    public function publishPlan(): void
    {
        EventAccess::authorize();
        $p = app(PublicPlan::class)->publish($this->selectedScenario());
        $this->notice('Publication '.$p->number.' enregistrée.');
    }

    public function withdrawPlan(): void
    {
        EventAccess::authorize();
        $this->selectedEvent()->update(['published_revision_id' => null]);
        $this->notice('Publication retirée ; versions conservées.');
    }

    public function saveReview(): void
    {
        EventAccess::authorize();
        $scenario = $this->selectedScenario();
        abort_unless($scenario, 422);
        $element = $this->scopedElement($this->review['element_id'] ?: $this->elementId);
        $offer = ! empty($this->review['offer_id']) ? $scenario->offers()->findOrFail($this->review['offer_id']) : null;
        if ($this->review['level'] === 'offer') {
            abort_unless($offer, 422, 'Sélectionner l’offre évaluée.');
        }
        if (in_array($this->review['level'], ['element', 'zone'], true)) {
            abort_unless($element, 422, 'Sélectionner le lieu évalué.');
        }
        $this->validate(['review.name' => 'required|string|max:200', 'review.level' => 'required|in:organization,event,scenario,zone,element,offer,channel',
            'review.audience' => ['required', Rule::in(array_keys(config('event_planner.audiences')))], 'review.evidence_kind' => 'required|in:hypothesis,observed,simulated',
            'review.decision' => 'required|in:keep,improve,move,expand,remove', 'review.due_at' => 'nullable|date',
            'review.observed' => 'nullable|string|max:255', 'review.evidence_reference' => 'nullable|string|max:2000', 'review.owner' => 'nullable|string|max:200',
            'review.name' => 'required|string|max:200', 'review.product' => 'nullable|string|max:4000', 'review.price' => 'nullable|string|max:4000',
            'review.place' => 'nullable|string|max:4000', 'review.promotion' => 'nullable|string|max:4000', 'review.hypothesis' => 'nullable|string|max:4000',
            'review.indicator' => 'nullable|string|max:255', 'review.unit' => 'nullable|string|max:100', 'review.target' => 'nullable|string|max:255', 'review.next_action' => 'nullable|string|max:4000']);
        if ($this->review['evidence_kind'] === 'observed') {
            $this->validate(['review.observed' => 'required|string|max:255', 'review.evidence_reference' => 'required|string|max:2000']);
        }
        $r = $this->review;
        FourPReview::create(['event_id' => $scenario->event_id, 'scenario_id' => $scenario->id, 'element_id' => $element?->id, 'offer_id' => $offer?->id, 'level' => $r['level'], 'audience' => $r['audience'], 'name' => $r['name'],
            'four_ps' => array_intersect_key($r, array_flip(['product', 'price', 'place', 'promotion'])), 'hypothesis' => $r['hypothesis'], 'indicator' => $r['indicator'], 'unit' => $r['unit'],
            'target' => $r['target'] === '' ? null : $r['target'], 'observed' => $r['observed'] === '' ? null : $r['observed'], 'evidence_kind' => $r['evidence_kind'], 'evidence_reference' => $r['evidence_reference'] ?: null,
            'observed_at' => $r['evidence_kind'] === 'observed' ? now() : null, 'owner' => $r['owner'], 'due_at' => $r['due_at'] ?: null, 'next_action' => $r['next_action'], 'decision' => $r['decision']]);
        $this->notice('Hypothèse, preuve et décision enregistrées.');
    }

    public function saveCampaign(): void
    {
        EventAccess::authorize();
        $this->selectedEvent();
        $this->validate(['campaign.name' => 'required|string|max:200', 'campaign.audience' => ['required', Rule::in(array_keys(config('event_planner.audiences')))],
            'campaign.phase' => 'required|in:teaser,applications,official', 'campaign.message' => 'required|string|max:10000', 'campaign.channel' => 'required|string|max:100',
            'campaign.call_to_action' => 'required|string|max:200', 'campaign.owner' => 'nullable|string|max:200', 'campaign.due_at' => 'nullable|date',
            'campaign.state' => 'required|in:draft,approved,delivered', 'campaign.delivery_reference' => 'nullable|string|max:255']);
        if ($this->campaign['state'] === 'delivered') {
            $this->validate(['campaign.delivery_reference' => 'required|string|max:255']);
            if ($this->campaign['phase'] === 'official') {
                abort_unless($this->selectedEvent()->decision === 'confirmed', 422, 'Événement non confirmé.');
            }
        }
        $c = array_intersect_key($this->campaign, array_flip(['name', 'audience', 'phase', 'message', 'channel', 'call_to_action', 'owner', 'due_at', 'state', 'delivery_reference']));
        $c['event_id'] = $this->eventId;
        $c['budget_cents'] = Money::parse($this->campaign['budget']) ?? 0;
        $c['due_at'] = $c['due_at'] ?: null;
        if ($this->campaign['id']) {
            $this->selectedEvent()->campaigns()->findOrFail($this->campaign['id'])->update($c);
        } else {
            $this->selectedEvent()->campaigns()->create($c);
        }
        $this->notice('Campagne enregistrée. Aucun message n’a été envoyé.');
    }

    public function editCampaign(int $id): void
    {
        $c = $this->selectedEvent()->campaigns()->findOrFail($id);
        $this->campaign = array_replace($this->campaign, $c->only(['id', 'name', 'audience', 'phase', 'message', 'channel', 'call_to_action', 'owner', 'state', 'delivery_reference']));
        $this->campaign['due_at'] = $c->due_at?->format('Y-m-d\TH:i') ?? '';
        $this->campaign['budget'] = Money::input($c->budget_cents);
    }

    public function editLead(int $id): void
    {
        $l = $this->selectedEvent()->leads()->findOrFail($id);
        $this->lead = array_replace($this->lead, $l->only(['id', 'name', 'email', 'audience', 'source', 'stage', 'owner', 'next_action', 'notes']));
        $this->lead['due_at'] = $l->due_at?->format('Y-m-d\TH:i') ?? '';
    }

    public function saveLead(): void
    {
        EventAccess::authorize();
        $this->validate(['lead.name' => 'required|string|max:200', 'lead.email' => 'required|email|max:254', 'lead.audience' => ['required', Rule::in(array_keys(config('event_planner.audiences')))],
            'lead.source' => 'nullable|string|max:200', 'lead.stage' => 'required|in:contact,qualified,proposal,conditional,confirmed,delivered,feedback,declined',
            'lead.owner' => 'nullable|string|max:200', 'lead.next_action' => 'nullable|string|max:2000', 'lead.notes' => 'nullable|string|max:5000', 'lead.due_at' => 'nullable|date']);
        $l = array_intersect_key($this->lead, array_flip(['name', 'email', 'audience', 'source', 'stage', 'owner', 'next_action', 'notes', 'due_at']));
        $l['due_at'] = $l['due_at'] ?: null;
        if ($this->lead['id']) {
            $this->selectedEvent()->leads()->findOrFail($this->lead['id'])->update($l);
        } else {
            $this->selectedEvent()->leads()->create($l);
        }
        $this->notice('Dossier commercial enregistré.');
    }

    public function editProduct(int $id): void
    {
        $p = $this->selectedScenario()->products()->findOrFail($id);
        $this->product = array_replace($this->product, $p->only(['id', 'sku', 'name', 'seller', 'element_id', 'is_public']));
        foreach (['price' => 'price_cents', 'net_price' => 'net_price_cents', 'unit_cost' => 'unit_cost_cents'] as $k => $f) {
            $this->product[$k] = Money::input($p->$f);
        } $this->product['description'] = $p->content['description'] ?? '';
    }

    public function saveProduct(): void
    {
        EventAccess::authorize();
        $s = $this->selectedScenario();
        $this->validate(['product.sku' => 'required|string|max:100', 'product.name' => 'required|string|max:200', 'product.seller' => 'required|in:organizer,independent',
            'product.is_public' => 'boolean', 'product.description' => 'nullable|string|max:5000']);
        $price = Money::parse($this->product['price']);
        abort_if($price === null, 422, 'Prix requis.');
        $element = $this->scopedElement($this->product['element_id'] ?? null);
        $values = ['sku' => $this->product['sku'], 'name' => $this->product['name'], 'seller' => $this->product['seller'], 'price_cents' => $price,
            'net_price_cents' => Money::parse($this->product['net_price']), 'unit_cost_cents' => Money::parse($this->product['unit_cost']),
            'element_id' => $element?->id, 'is_public' => $this->product['is_public'], 'content' => ['description' => $this->product['description']]];
        if ($this->product['id']) {
            $s->products()->findOrFail($this->product['id'])->update($values);
        } else {
            $s->products()->create($values);
        }
        $this->notice('Produit enregistré.');
    }

    public function recordMovement(): void
    {
        EventAccess::authorize();
        $p = $this->selectedScenario()->products()->findOrFail($this->movement['product_id']);
        $point = $this->scopedElement($this->movement['element_id'] ?? null);
        $source = $this->scopedElement($this->movement['source_element_id'] ?? null);
        app(StockLedger::class)->move($p, $this->movement['kind'], (int) $this->movement['quantity'], $this->movement['reference'], $point?->id, $this->movement['reason'], $source?->id);
        $this->notice('Mouvement enregistré et rapproché.');
    }

    private function scopedElement(mixed $id): ?PlanElement
    {
        return $id ? $this->selectedScenario()->elements()->findOrFail($id) : null;
    }

    private function notice(string $message): void
    {
        Notification::make()->title($message)->success()->send();
    }

    protected function getViewData(): array
    {
        $s = $this->selectedScenario();
        $e = $this->selectedEvent();

        return ['module' => $this->module, 'events' => $this->events()->orderBy('name')->get(), 'event' => $e, 'scenario' => $s,
            'scenarios' => $e?->scenarios()->get() ?? collect(), 'elements' => $s?->elements()->whereNull('archived_at')->get() ?? collect(),
            'lines' => $s?->budgetLines()->with('element')->get() ?? collect(), 'offers' => $s?->offers()->with('pool')->get() ?? collect(),
            'pools' => $s?->pools()->get() ?? collect(), 'bookings' => $e?->bookings()->with('offer', 'refund')->get() ?? collect(),
            'reviews' => $e?->reviews()->get() ?? collect(), 'campaigns' => $e?->campaigns()->get() ?? collect(), 'leads' => $e?->leads()->get() ?? collect(),
            'products' => $s?->products()->with('locations.element')->get() ?? collect(), 'finance' => $s ? app(EventFinance::class)->summarize($s) : null,
            'comparison' => $this->module === 'balance' && $e ? $e->scenarios()->get()->map(fn ($variant) => ['scenario' => $variant, 'finance' => app(EventFinance::class)->summarize($variant)]) : collect()];
    }
}
