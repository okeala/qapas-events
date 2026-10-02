<?php

namespace App\Services\Events;

use App\Models\EventBooking;
use App\Models\EventScenario;

class EventFinance
{
    public function summarize(EventScenario $scenario): array
    {
        $lines = $scenario->budgetLines()->where('status', '!=', 'cancelled')->get();
        $costs = $lines->where('kind', 'cost');
        $operating = $costs->where('cost_class', 'operating');
        $revenues = $lines->where('kind', 'revenue');
        $bookings = EventBooking::query()->whereHas('offer', fn ($q) => $q->where('scenario_id', $scenario->id))->with('refund')->get();
        $acquiredOrders = $bookings->whereIn('state', ['paid', 'confirmed']);
        $acquiredLines = $revenues->where('status', 'paid')->where('earmarked', false);
        $knownCost = (int) $operating->sum('net_cents');
        $acquired = (int) $acquiredLines->sum('net_cents') + (int) $acquiredOrders->sum('net_cents');
        $forecast = (int) $revenues->sum('net_cents');
        $quotedOffers = $scenario->offers()->where('seller', 'organizer')->where('active', true)->get();
        $forecast += $quotedOffers->sum(fn ($o) => (int) $o->net_price_cents * (int) ($o->content['target_quantity'] ?? 0));
        $cashIn = (int) $revenues->where('status', 'paid')->sum('gross_cents') + (int) $bookings->whereNotNull('paid_at')->sum('gross_cents');
        $cashOut = (int) $costs->where('status', 'paid')->sum('gross_cents');
        $refunded = (int) $bookings->sum(fn ($b) => $b->refund?->state === 'completed' ? $b->refund->amount_cents : 0);
        $reserved = (int) $bookings->whereIn('state', ['paid', 'refund_pending'])->sum('gross_cents');
        $required = $knownCost + $scenario->contingency_cents + ($scenario->profit_required ? $scenario->target_profit_cents : 0);
        $blockers = [];
        $unknown = $costs->filter(fn ($l) => $l->net_cents === null || $l->gross_cents === null || ! $l->source_reference)->count();
        if ($unknown) {
            $blockers[] = "$unknown coût(s) inclus dans ce scénario à chiffrer ou justifier.";
        }
        if ($operating->isEmpty()) {
            $blockers[] = 'Le budget du format de base est vide.';
        }
        if ($acquiredLines->contains(fn ($l) => $l->net_cents === null) || $acquiredOrders->contains(fn ($o) => $o->net_cents === null)) {
            $blockers[] = 'La fiscalité ou le montant affectable de recettes acquises reste inconnu.';
        }
        if ($acquiredOrders->contains(fn ($b) => ! $costs->contains('reference_key', 'delivery:'.$b->uuid))) {
            $blockers[] = 'Une livraison de prestation précommandée manque au budget.';
        }
        if ($acquired < $required) {
            $blockers[] = 'Recettes acquises et affectables insuffisantes pour le format de base.';
        }
        foreach (config('event_planner.prerequisites') as $key => $label) {
            if (($scenario->prerequisites[$key] ?? false) !== true) {
                $blockers[] = $label.' : preuve manquante.';
            }
        }
        if (! $scenario->readiness_reference) {
            $blockers[] = 'Référence du dossier de confirmation manquante.';
        }
        $remainingGross = (int) $costs->where('status', '!=', 'paid')->sum('gross_cents');
        $earmarkedCash = (int) $revenues->where('status', 'paid')->where('earmarked', true)->sum('gross_cents');
        $availableCash = $scenario->own_cash_cents + $cashIn - $cashOut - $refunded - $reserved - $earmarkedCash;
        if ($scenario->event->decision === 'pending' && $availableCash < 0) {
            $blockers[] = 'Les fonds réservés aux clients ne sont plus intégralement couverts : reconstituer la réserve.';
        }
        if ($scenario->own_cash_cents + $cashIn - $cashOut - $refunded - $earmarkedCash < $remainingGross + $scenario->contingency_cents) {
            $blockers[] = 'Trésorerie vérifiée insuffisante pour les décaissements prévus.';
        }
        $paymentReferences = $bookings->whereNotNull('payment_reference')->pluck('payment_reference')->all();
        if ($revenues->whereIn('source_reference', $paymentReferences)->isNotEmpty()) {
            $blockers[] = 'Un paiement de commande est aussi saisi comme recette : rapprocher la pièce pour éviter un double compte.';
        }
        if (! $scenario->is_base) {
            $blockers[] = 'Sélectionner le scénario du format de base.';
        }
        if ($scenario->event->overdue()) {
            $blockers[] = 'Le délai commun de décision est expiré.';
        }

        return ['operating_cents' => $knownCost, 'investment_cents' => (int) $costs->where('cost_class', 'investment')->sum('net_cents'),
            'inventory_cash_cents' => (int) $costs->where('cost_class', 'inventory')->sum('gross_cents'),
            'forecast_revenue_cents' => $forecast, 'acquired_revenue_cents' => $acquired,
            'forecast_result_cents' => $forecast - $knownCost, 'base_result_cents' => $acquired - $knownCost,
            'target_profit_cents' => $scenario->target_profit_cents, 'required_cents' => $required, 'unknown_costs' => $unknown,
            'received_cash_cents' => $cashIn, 'spent_cash_cents' => $cashOut + $refunded, 'reserved_cash_cents' => $reserved,
            'earmarked_cash_cents' => $earmarkedCash,
            'available_cash_cents' => $availableCash,
            'confirmation_blockers' => $blockers, 'can_confirm' => $blockers === []];
    }
}
