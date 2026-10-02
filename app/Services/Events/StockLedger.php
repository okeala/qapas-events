<?php

namespace App\Services\Events;

use App\Models\EventBudgetLine;
use App\Models\EventProduct;
use App\Models\EventScenario;
use App\Models\EventStockLocation;
use App\Models\EventStockMovement;
use App\Models\PlannerEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockLedger
{
    public function move(EventProduct $product, string $kind, int $quantity, string $reference, ?int $point = null, string $reason = '', ?int $source = null): EventStockMovement
    {
        Validator::make(compact('kind', 'quantity', 'reference', 'reason'), ['kind' => 'required|in:receipt,sale,return,adjustment,loss,transfer',
            'quantity' => 'required|integer|between:1,100000', 'reference' => 'required|string|max:200', 'reason' => 'nullable|string|max:2000'])->validate();

        return DB::transaction(function () use ($product, $kind, $quantity, $reference, $point, $reason, $source) {
            PlannerEvent::query()->lockForUpdate()->findOrFail($product->scenario->event_id);
            EventScenario::query()->lockForUpdate()->findOrFail($product->scenario_id);
            $product = EventProduct::query()->lockForUpdate()->findOrFail($product->id);
            if ($point) {
                abort_unless($product->scenario->elements()->whereKey($point)->exists(), 422, 'Point de vente hors scénario.');
            }
            if ($source) {
                abort_unless($product->scenario->elements()->whereKey($source)->exists(), 422, 'Source hors scénario.');
            }
            if ($kind === 'transfer') {
                abort_if($point === $source, 422, 'Choisir deux emplacements distincts.');
            } else {
                abort_if($source !== null, 422, 'La source est réservée aux transferts.');
            }
            $existing = EventStockMovement::query()->where('reference', $reference)->first();
            if ($existing) {
                abort_unless($existing->product_id === $product->id && $existing->kind === $kind && abs($existing->quantity) === $quantity && $existing->element_id === $point && $existing->source_element_id === $source, 409);

                return $existing;
            }
            $location = $this->location($product, $point);
            if ($kind === 'transfer') {
                $from = $this->location($product, $source);
                abort_if($from->quantity < $quantity, 422, 'Stock insuffisant à la source.');
                $from->decrement('quantity', $quantity);
                $location->increment('quantity', $quantity);
                $delta = 0;
            } else {
                $delta = in_array($kind, ['sale', 'loss'], true) ? -$quantity : $quantity;
                abort_if($location->quantity + $delta < 0, 422, 'Stock insuffisant à cet emplacement.');
                $location->increment('quantity', $delta);
            }
            abort_if($product->stock + $delta < 0, 422, 'Stock insuffisant.');
            $product->increment('stock', $delta);
            $movement = EventStockMovement::create(['product_id' => $product->id, 'reference' => $reference, 'kind' => $kind, 'quantity' => $kind === 'transfer' ? $quantity : $delta,
                'gross_cents' => $kind === 'sale' ? $product->price_cents * $quantity : 0, 'net_cents' => $kind === 'sale' && $product->net_price_cents !== null ? $product->net_price_cents * $quantity : null,
                'element_id' => $point, 'source_element_id' => $source, 'reason' => $reason]);
            if ($product->seller === 'organizer' && in_array($kind, ['sale', 'loss'], true)) {
                if ($kind === 'sale') {
                    EventBudgetLine::create(['scenario_id' => $product->scenario_id, 'element_id' => $movement->element_id, 'reference_key' => 'sale:'.$movement->id,
                        'label' => 'Vente : '.$product->name, 'kind' => 'revenue', 'status' => 'paid', 'gross_cents' => $movement->gross_cents, 'net_cents' => $movement->net_cents, 'source_reference' => $reference]);
                }
                EventBudgetLine::create(['scenario_id' => $product->scenario_id, 'element_id' => $movement->element_id, 'reference_key' => 'consumption:'.$movement->id,
                    'label' => ($kind === 'loss' ? 'Perte de stock : ' : 'Stock consommé : ').$product->name, 'kind' => 'cost', 'status' => 'paid', 'gross_cents' => 0,
                    'net_cents' => $product->unit_cost_cents === null ? null : $product->unit_cost_cents * $quantity, 'source_reference' => $reference,
                    'metadata' => ['noncash' => true, 'product_id' => $product->id, 'stock_movement_id' => $movement->id]]);
            }

            return $movement;
        }, 3);
    }

    private function location(EventProduct $product, ?int $element): EventStockLocation
    {
        return EventStockLocation::firstOrCreate(['product_id' => $product->id, 'location_key' => $element ? 'element:'.$element : 'depot'], ['element_id' => $element, 'quantity' => 0]);
    }
}
