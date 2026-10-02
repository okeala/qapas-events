<?php

namespace App\Services\Events;

use App\Models\EventAuditEntry;
use App\Models\EventBudgetLine;
use App\Models\EventScenario;
use App\Models\PlanElement;
use App\Models\PlannerEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PlanWriter
{
    public function save(EventScenario $scenario, array $data, ?PlanElement $element = null): PlanElement
    {
        Validator::make($data, ['name' => 'required|string|max:160', 'category' => 'required|string', 'subcategory' => 'required|string',
            'shape' => 'required|string', 'color' => 'required|regex:/^#[0-9a-fA-F]{6}$/', 'dimensions' => 'nullable|array', 'geometry' => 'nullable|array',
            'four_ps' => 'nullable|array', 'four_ps.product' => 'nullable|string|max:4000', 'four_ps.price' => 'nullable|string|max:4000',
            'four_ps.place' => 'nullable|string|max:4000', 'four_ps.promotion' => 'nullable|string|max:4000', 'success' => 'nullable|array',
            'success.objective' => 'nullable|string|max:4000', 'success.target' => 'nullable|string|max:1000', 'success.observed' => 'nullable|string|max:1000', 'success.evidence' => 'nullable|string|max:2000|required_with:success.observed',
            'public_content' => 'nullable|array', 'public_content.description' => 'nullable|string|max:10000',
            'public_content.hours' => 'nullable|string|max:200', 'public_content.access' => 'nullable|string|max:1000',
            'public_content.public_price' => 'nullable|string|max:1000', 'public_content.story' => 'nullable|string|max:6000',
            'is_public' => 'boolean', 'is_essential' => 'boolean', 'tour_order' => 'nullable|integer|min:0', 'operations' => 'nullable|array'])->validate();
        $category = config('event_planner.categories.'.$data['category']);
        abort_unless($category && isset($category['items'][$data['subcategory']]), 422, 'Sous-catégorie inconnue.');
        abort_unless(isset(PlanGeometry::shapesFor($data['category'])[$data['shape']]), 422, 'Les équipements gardent un gabarit régulier ; dessinez un espace pour une emprise libre.');
        $geometry = app(PlanGeometry::class)->make($data['shape'], $data['dimensions'] ?? [], $data['geometry'] ?? null);

        return DB::transaction(function () use ($scenario, $data, $element, $geometry) {
            PlannerEvent::query()->lockForUpdate()->findOrFail($scenario->event_id);
            $scenario = EventScenario::query()->lockForUpdate()->findOrFail($scenario->id);
            if ($element) {
                $element = PlanElement::query()->lockForUpdate()->findOrFail($element->id);
                abort_unless($element->scenario_id === $scenario->id, 404);
                abort_unless((int) ($data['expected_revision'] ?? -1) === $element->revision, 409, 'Cet élément a été modifié. Rechargez sa fiche.');
            } else {
                $element = new PlanElement(['scenario_id' => $scenario->id]);
            }
            $ops = $data['operations'] ?? [];
            Validator::make($ops, ['organization' => 'nullable|string|max:200', 'person_reference' => 'nullable|string|max:200',
                'resource_reference' => 'nullable|string|max:255', 'responsible' => 'nullable|string|max:200', 'notes' => 'nullable|string|max:5000',
                'shift_start' => 'nullable|date|required_with:shift_end', 'shift_end' => 'nullable|date|after:shift_start|required_with:shift_start'])->validate();
            if (! empty($ops['person_reference']) && ! empty($ops['shift_start'])) {
                foreach ($scenario->elements()->whereNull('archived_at')->where('id', '!=', $element->id ?? 0)->get() as $other) {
                    $shift = $other->operations ?? [];
                    if (($shift['person_reference'] ?? null) === $ops['person_reference'] && ! empty($shift['shift_start']) && ! empty($shift['shift_end'])
                        && strtotime($ops['shift_start']) < strtotime($shift['shift_end']) && strtotime($ops['shift_end']) > strtotime($shift['shift_start'])) {
                        throw ValidationException::withMessages(['plan.operations.person_reference' => 'Cette personne est déjà affectée sur un créneau chevauchant.']);
                    }
                }
            }
            $fields = array_intersect_key($data, array_flip(['name', 'category', 'subcategory', 'shape', 'dimensions', 'color', 'four_ps', 'success', 'public_content', 'operations', 'is_public', 'is_essential', 'tour_order']));
            $fields['geometry'] = $geometry;
            $fields['revision'] = $element->revision + 1;
            $element->fill($fields)->save();
            $measurements = app(PlanGeometry::class)->measurements($geometry);
            $line = EventBudgetLine::query()->firstOrCreate(['scenario_id' => $scenario->id, 'reference_key' => 'element:'.$element->uuid],
                ['element_id' => $element->id, 'label' => 'Coût de '.$element->name, 'kind' => 'cost', 'gross_cents' => null, 'net_cents' => null, 'essential' => $element->is_essential, 'metadata' => ['measurements' => $measurements]]);
            $line->update(['essential' => $element->is_essential, 'metadata' => array_merge($line->metadata ?? [], ['measurements' => $measurements])]);
            $scenario->increment('revision');
            EventAuditEntry::create(['event_id' => $scenario->event_id, 'action' => 'plan.saved', 'reference' => $element->uuid, 'admin_id' => auth('admin')->id(), 'details' => ['revision' => $element->revision, 'measurements' => $measurements]]);

            return $element->refresh();
        });
    }

    public function archive(PlanElement $element): void
    {
        DB::transaction(function () use ($element) {
            abort_if($element->offers()->whereHas('bookings', fn ($q) => $q->whereIn('state', ['signed', 'paid', 'confirmed']))->exists(), 422, 'Cet élément porte des engagements actifs : réconcilier leur livraison avant de le retirer.');
            $element->update(['archived_at' => now()]);
            $element->offers()->update(['active' => false]);
            $element->scenario()->increment('revision');
            $element->budgetLines()->where('status', 'forecast')->update(['status' => 'cancelled']);
            EventAuditEntry::create(['event_id' => $element->scenario->event_id, 'action' => 'plan.archived', 'reference' => $element->uuid, 'admin_id' => auth('admin')->id()]);
        });
    }

    public function duplicate(PlanElement $element): PlanElement
    {
        $data = $element->only(['name', 'category', 'subcategory', 'shape', 'geometry', 'dimensions', 'color', 'four_ps', 'success', 'public_content', 'is_essential']);
        $data['name'] .= ' — copie';
        $data['is_public'] = false;
        $data['operations'] = ['needs' => $element->operations['needs'] ?? []];
        if (isset($data['dimensions']['lat'])) {
            $data['dimensions']['lat'] += 0.00004;
        }

        return $this->save($element->scenario, $data);
    }
}
