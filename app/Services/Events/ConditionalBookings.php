<?php

namespace App\Services\Events;

use App\Models\EventAuditEntry;
use App\Models\EventBooking;
use App\Models\EventBudgetLine;
use App\Models\EventCapacityPool;
use App\Models\EventOffer;
use App\Models\EventRefund;
use App\Models\EventScenario;
use App\Models\PlannerEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ConditionalBookings
{
    public function sign(EventOffer $offer, array $data): EventBooking
    {
        Validator::make($data, ['buyer_name' => 'required|string|max:200', 'buyer_email' => 'required|email|max:254',
            'quantity' => 'required|integer|min:1|max:10000', 'agreement_reference' => 'required|string|max:255',
            'idempotency_key' => 'required|string|max:100', 'signed_at' => 'nullable|date|before_or_equal:now'])->validate();

        return DB::transaction(function () use ($offer, $data) {
            $event = PlannerEvent::query()->lockForUpdate()->findOrFail($offer->scenario->event_id);
            $offer = EventOffer::query()->lockForUpdate()->findOrFail($offer->id);
            $existing = EventBooking::query()->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                abort_unless($existing->offer_id === $offer->id && $existing->quantity === (int) $data['quantity'] && $existing->buyer_email === $data['buyer_email'], 409, 'La clé de commande est déjà utilisée.');

                return $existing;
            }
            abort_unless($offer->active && $offer->seller === 'organizer', 422, 'Offre indisponible.');
            abort_unless(in_array($event->decision, ['pending', 'confirmed'], true) && ! $event->overdue(), 422, 'Cette édition n’accepte plus de précommandes.');
            abort_unless(in_array($event->phase, ['applications', 'official'], true), 422, 'Les précommandes ne sont pas ouvertes.');
            if ($event->decision === 'confirmed') {
                abort_unless($event->confirmed_scenario_id === $offer->scenario_id, 422, 'Ce scénario n’est pas le format confirmé.');
            }
            $signed = (isset($data['signed_at']) ? CarbonImmutable::parse($data['signed_at'], $event->timezone) : CarbonImmutable::now())->utc();
            if ($event->first_signed_at && $signed->lessThan($event->first_signed_at)) {
                throw ValidationException::withMessages(['signed_at' => 'Une signature antérieure exige une réconciliation du dossier ; l’échéance existante ne peut être déplacée.']);
            }
            if (! $event->first_signed_at && $event->decision === 'pending') {
                $days = min(21, max(1, $event->decision_days));
                $event->update(['first_signed_at' => $signed, 'decision_deadline' => $signed->setTimezone($event->timezone)->addDays($days)->utc()]);
                abort_if($event->overdue(), 422, 'Cette première signature est déjà hors délai de décision.');
            }
            $quantity = (int) $data['quantity'];
            $booking = EventBooking::create(['uuid' => (string) Str::uuid(), 'event_id' => $event->id, 'offer_id' => $offer->id,
                'buyer_name' => $data['buyer_name'], 'buyer_email' => $data['buyer_email'], 'buyer_subject' => $data['buyer_subject'] ?? null,
                'quantity' => $quantity, 'price_version' => $offer->price_version, 'gross_cents' => $offer->price_cents * $quantity,
                'net_cents' => $offer->net_price_cents === null ? null : $offer->net_price_cents * $quantity,
                'offer_snapshot' => $offer->only(['name', 'price_cents', 'net_price_cents', 'delivery_cost_cents', 'delivery_gross_cents', 'price_version', 'content', 'audience', 'seller']),
                'signed_at' => $signed, 'deadline' => $event->decision_deadline ?? $event->decided_at ?? now(),
                'agreement_reference' => $data['agreement_reference'], 'idempotency_key' => $data['idempotency_key'], 'state' => 'signed']);
            $this->audit($event, 'booking.signed', $booking->uuid, ['deadline' => $booking->deadline->toIso8601String()]);

            return $booking;
        }, 3);
    }

    public function recordPayment(EventBooking $booking, string $reference): EventBooking
    {
        Validator::make(['reference' => $reference], ['reference' => 'required|string|max:255'])->validate();

        return DB::transaction(function () use ($booking, $reference) {
            $event = PlannerEvent::query()->lockForUpdate()->findOrFail($booking->event_id);
            $booking = EventBooking::query()->lockForUpdate()->with('offer')->findOrFail($booking->id);
            if ($booking->paid_at) {
                abort_unless($booking->payment_reference === $reference, 409);

                return $booking;
            }
            abort_unless($booking->state === 'signed' && ! $event->overdue() && in_array($event->decision, ['pending', 'confirmed'], true), 422, 'Paiement à rapprocher hors du parcours actif.');
            if ($booking->offer->pool_id) {
                $changed = EventCapacityPool::query()->whereKey($booking->offer->pool_id)
                    ->whereRaw('reserved + ? <= capacity', [$booking->quantity])->increment('reserved', $booking->quantity);
                abort_unless($changed === 1, 422, 'Capacité épuisée ; ne pas affecter cet encaissement à une place indisponible.');
            }
            $booking->update(['state' => $event->decision === 'confirmed' ? 'confirmed' : 'paid', 'paid_at' => now(), 'payment_reference' => $reference]);
            $quote = $booking->offer_snapshot;
            EventBudgetLine::firstOrCreate(['scenario_id' => $booking->offer->scenario_id, 'reference_key' => 'delivery:'.$booking->uuid], [
                'element_id' => $booking->offer->element_id, 'label' => 'Livraison additionnelle : '.$quote['name'], 'kind' => 'cost', 'status' => 'forecast',
                'gross_cents' => ($quote['delivery_gross_cents'] ?? null) === null ? null : $quote['delivery_gross_cents'] * $booking->quantity,
                'net_cents' => ($quote['delivery_cost_cents'] ?? null) === null ? null : $quote['delivery_cost_cents'] * $booking->quantity,
                'source_reference' => $quote['content']['delivery_reference'] ?? null, 'essential' => true,
                'metadata' => ['booking_uuid' => $booking->uuid, 'quantity' => $booking->quantity, 'price_version' => $booking->price_version],
            ]);
            $this->audit($event, 'booking.payment_verified', $booking->uuid, ['gross_cents' => $booking->gross_cents]);

            return $booking->refresh();
        }, 3);
    }

    public function confirm(EventScenario $scenario): void
    {
        DB::transaction(function () use ($scenario) {
            $event = PlannerEvent::query()->lockForUpdate()->findOrFail($scenario->event_id);
            if ($event->decision === 'confirmed') {
                abort_unless($event->confirmed_scenario_id === $scenario->id, 409);

                return;
            }
            abort_unless($event->decision === 'pending' && ! $event->overdue(), 422, 'Décision expirée ou déjà prise.');
            $scenario = EventScenario::query()->lockForUpdate()->findOrFail($scenario->id);
            $summary = app(EventFinance::class)->summarize($scenario);
            if (! $summary['can_confirm']) {
                throw ValidationException::withMessages(['confirmation' => implode(' ', $summary['confirmation_blockers'])]);
            }
            abort_if($event->bookings()->where('state', 'paid')->whereHas('offer', fn ($q) => $q->where('scenario_id', '!=', $scenario->id))->exists(), 422, 'Des précommandes portent sur un autre scénario ; réconcilier leurs engagements.');
            $event->update(['decision' => 'confirmed', 'phase' => 'official', 'decided_at' => now(), 'confirmed_scenario_id' => $scenario->id]);
            $event->bookings()->where('state', 'paid')->update(['state' => 'confirmed']);
            $this->audit($event, 'event.confirmed', (string) $scenario->id, ['financial' => $summary, 'dossier' => $scenario->readiness_reference]);
            app(PublicPlan::class)->publish($scenario->refresh());
        }, 3);
    }

    public function reject(PlannerEvent $event, bool $expired = false): void
    {
        DB::transaction(function () use ($event, $expired) {
            $event = PlannerEvent::query()->lockForUpdate()->findOrFail($event->id);
            if ($event->decision === 'not_confirmed') {
                return;
            }
            abort_unless($event->decision === 'pending', 422, 'Une annulation après confirmation exige un dossier distinct.');
            if ($expired) {
                abort_unless($event->overdue(), 422);
            }
            $event->update(['decision' => 'not_confirmed', 'decided_at' => now()]);
            foreach ($event->bookings()->whereIn('state', ['signed', 'paid'])->lockForUpdate()->get() as $booking) {
                if ($booking->paid_at) {
                    EventRefund::firstOrCreate(['booking_id' => $booking->id], ['amount_cents' => $booking->gross_cents, 'state' => 'requested']);
                    $booking->update(['state' => 'refund_pending']);
                    EventBudgetLine::query()->where('reference_key', 'delivery:'.$booking->uuid)->where('status', 'forecast')->update(['status' => 'cancelled']);
                    if ($booking->offer->pool_id) {
                        EventCapacityPool::query()->whereKey($booking->offer->pool_id)->decrement('reserved', $booking->quantity);
                    }
                } else {
                    $booking->update(['state' => 'expired']);
                }
            }
            $this->audit($event, $expired ? 'event.deadline_expired' : 'event.not_confirmed', $event->uuid);
        }, 3);
    }

    public function recordRefund(EventRefund $refund, string $reference, bool $completed = false): void
    {
        Validator::make(['reference' => $reference], ['reference' => 'required|string|max:255'])->validate();
        DB::transaction(function () use ($refund, $reference, $completed) {
            $refund = EventRefund::query()->lockForUpdate()->with('booking')->findOrFail($refund->id);
            if ($refund->state === 'completed') {
                abort_unless($refund->reference === $reference, 409);

                return;
            }
            $refund->update(['reference' => $reference, 'state' => $completed ? 'completed' : 'submitted', 'submitted_at' => $refund->submitted_at ?? now(), 'completed_at' => $completed ? now() : null]);
            if ($completed) {
                $refund->booking->update(['state' => 'refunded']);
            }
            $this->audit($refund->booking->event, $completed ? 'refund.completed_verified' : 'refund.submitted_verified', $refund->booking->uuid, ['amount_cents' => $refund->amount_cents]);
        });
    }

    private function audit(PlannerEvent $event, string $action, string $reference, array $details = []): void
    {
        EventAuditEntry::create(['event_id' => $event->id, 'action' => $action, 'reference' => $reference, 'admin_id' => auth('admin')->id(), 'details' => $details]);
    }
}
