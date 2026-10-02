<?php

namespace App\Services\Events;

use App\Models\EventAuditEntry;
use App\Models\EventBudgetLine;
use App\Models\EventScenario;
use App\Models\PlannerEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BudgetWriter
{
    public function save(EventScenario $scenario, array $values, ?int $id = null): EventBudgetLine
    {
        Validator::make($values, ['label' => 'required|string|max:200', 'kind' => 'required|in:cost,revenue',
            'cost_class' => 'required|in:operating,investment,inventory', 'status' => 'required|in:forecast,committed,invoiced,paid,cancelled',
            'gross_cents' => 'nullable|integer|min:0', 'net_cents' => 'nullable|integer|min:0', 'source_reference' => 'nullable|string|max:255',
            'essential' => 'boolean', 'earmarked' => 'boolean'])->validate();

        return DB::transaction(function () use ($scenario, $values, $id) {
            PlannerEvent::query()->lockForUpdate()->findOrFail($scenario->event_id);
            $scenario = EventScenario::query()->lockForUpdate()->findOrFail($scenario->id);
            if (! empty($values['element_id'])) {
                $scenario->elements()->findOrFail($values['element_id']);
            }
            if (in_array($values['status'], ['committed', 'invoiced', 'paid'], true)) {
                abort_unless($values['source_reference'] && $values['gross_cents'] !== null, 422, 'Une pièce engagée exige un montant TTC et une référence vérifiables.');
            }
            $line = $id ? $scenario->budgetLines()->lockForUpdate()->findOrFail($id) : new EventBudgetLine(['scenario_id' => $scenario->id]);
            if ($line->exists && in_array($line->status, ['committed', 'invoiced', 'paid'], true)) {
                foreach (['gross_cents', 'net_cents', 'kind', 'cost_class', 'element_id', 'earmarked', 'source_reference'] as $field) {
                    abort_unless($line->$field === ($values[$field] ?? null), 422, 'Une pièce engagée conserve son montant et son affectation.');
                }
                $rank = ['forecast' => 0, 'committed' => 1, 'invoiced' => 2, 'paid' => 3, 'cancelled' => -1];
                abort_unless($rank[$values['status']] >= $rank[$line->status], 422, 'Le statut d’une pièce engagée ne peut régresser.');
            }
            if ($line->exists && $line->reference_key && str_starts_with($line->reference_key, 'sale:')) {
                abort(422, 'Une vente rapprochée est conservée dans le journal.');
            }
            $before = $line->exists ? $line->only(array_keys($values)) : [];
            $line->fill(array_intersect_key($values, array_flip(['label', 'kind', 'cost_class', 'status', 'gross_cents', 'net_cents', 'source_reference', 'element_id', 'essential', 'earmarked'])))->save();
            EventAuditEntry::create(['event_id' => $scenario->event_id, 'action' => 'budget.saved', 'reference' => (string) $line->id, 'admin_id' => auth('admin')->id(), 'details' => ['before' => $before, 'after' => $values]]);

            return $line;
        });
    }
}
