<?php

namespace App\Services\Events;

use App\Models\EventAuditEntry;
use App\Models\EventScenario;
use App\Models\PlannerEvent;
use Illuminate\Support\Facades\DB;

class ScenarioCloner
{
    public function copy(EventScenario $source, string $name): EventScenario
    {
        validator(compact('name'), ['name' => 'required|string|max:120'])->validate();

        return DB::transaction(function () use ($source, $name) {
            PlannerEvent::query()->lockForUpdate()->findOrFail($source->event_id);
            $source = EventScenario::query()->lockForUpdate()->findOrFail($source->id);
            $copy = $source->replicate(['uuid', 'readiness_reference']);
            $copy->fill(['name' => $name, 'is_base' => false, 'revision' => 0, 'prerequisites' => [], 'own_cash_cents' => 0])->save();
            $elements = [];
            $pools = [];
            foreach ($source->elements()->whereNull('archived_at')->get() as $item) {
                $clone = $item->replicate(['uuid']);
                $clone->scenario_id = $copy->id;
                $clone->revision = 1;
                $clone->operations = ['needs' => $item->operations['needs'] ?? [], 'organization' => $item->operations['organization'] ?? 'QAPAS', 'status' => 'needed'];
                $clone->save();
                $elements[$item->id] = $clone;
            }
            foreach ($source->pools()->get() as $pool) {
                $clone = $pool->replicate();
                $clone->scenario_id = $copy->id;
                $clone->reserved = 0;
                $clone->save();
                $pools[$pool->id] = $clone->id;
            }
            foreach ($source->offers()->get() as $offer) {
                $clone = $offer->replicate(['uuid']);
                $clone->scenario_id = $copy->id;
                $clone->element_id = $elements[$offer->element_id]->id ?? null;
                $clone->pool_id = $pools[$offer->pool_id] ?? null;
                $clone->price_version = 1;
                $clone->is_public = false;
                $clone->save();
            }
            foreach ($source->budgetLines()->where('status', '!=', 'cancelled')->get() as $line) {
                if ($line->kind === 'revenue' || str_starts_with($line->reference_key ?? '', 'delivery:') || str_starts_with($line->reference_key ?? '', 'consumption:')) {
                    continue;
                }
                $clone = $line->replicate();
                $clone->scenario_id = $copy->id;
                $clone->element_id = $elements[$line->element_id]->id ?? null;
                if (str_starts_with($line->reference_key ?? '', 'element:')) {
                    $clone->reference_key = 'element:'.$elements[$line->element_id]->uuid;
                }
                $clone->status = 'forecast';
                $clone->metadata = array_merge($line->metadata ?? [], ['comparison_from' => $source->id]);
                $clone->save();
            }
            foreach ($source->products()->get() as $product) {
                $clone = $product->replicate();
                $clone->scenario_id = $copy->id;
                $clone->element_id = $elements[$product->element_id]->id ?? null;
                $clone->stock = 0;
                $clone->is_public = false;
                $clone->save();
            }
            EventAuditEntry::create(['event_id' => $source->event_id, 'action' => 'scenario.copied', 'reference' => (string) $copy->id, 'admin_id' => auth('admin')->id(), 'details' => ['from' => $source->id]]);

            return $copy;
        });
    }
}
