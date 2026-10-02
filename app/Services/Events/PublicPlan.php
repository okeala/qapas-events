<?php

namespace App\Services\Events;

use App\Models\EventOffer;
use App\Models\EventProduct;
use App\Models\EventPublication;
use App\Models\EventScenario;
use App\Models\PlannerEvent;
use Illuminate\Support\Facades\DB;

class PublicPlan
{
    public function snapshot(EventScenario $scenario): array
    {
        $event = $scenario->event;
        $description = [];
        $phaseCopy = [];
        $faq = [];
        foreach (config('qapas_application.locales') as $locale) {
            if (is_string($event->description[$locale] ?? null)) {
                $description[$locale] = $event->description[$locale];
            }
            foreach (config('event_planner.phases') as $phase => $label) {
                $copy = $event->settings['phase_copy'][$phase][$locale] ?? [];
                foreach (['title', 'message'] as $field) {
                    if (is_string($copy[$field] ?? null)) {
                        $phaseCopy[$phase][$locale][$field] = $copy[$field];
                    }
                }
            }
        }
        foreach ($event->settings['public_faq'] ?? [] as $item) {
            $public = [];
            foreach (config('qapas_application.locales') as $locale) {
                if (is_string($item['question'][$locale] ?? null) && is_string($item['answer'][$locale] ?? null) && trim($item['question'][$locale]) !== '' && trim($item['answer'][$locale]) !== '') {
                    $public['question'][$locale] = $item['question'][$locale];
                    $public['answer'][$locale] = $item['answer'][$locale];
                }
            }
            if ($public) {
                $faq[] = $public;
            }
        }
        $features = $scenario->elements()->where('is_public', true)->whereNull('archived_at')->orderBy('tour_order')->get()->map(function ($item) {
            $content = $item->public_content ?? [];

            return ['type' => 'Feature', 'id' => $item->uuid, 'geometry' => $item->geometry, 'properties' => [
                'uuid' => $item->uuid, 'name' => $item->name, 'category' => $item->category, 'subcategory' => $item->subcategory, 'color' => $item->color,
                'description' => $content['description'] ?? '', 'hours' => $content['hours'] ?? '', 'access' => $content['access'] ?? '',
                'public_price' => $content['public_price'] ?? '', 'tour_order' => $item->tour_order,
                'four_ps' => ['product' => $content['description'] ?? '', 'price' => $content['public_price'] ?? '', 'place' => $content['access'] ?? '', 'promotion' => $content['story'] ?? ''],
            ]];
        })->values()->all();
        $publicIds = $scenario->elements()->where('is_public', true)->whereNull('archived_at')->pluck('id')->all();
        $offers = $scenario->offers()->where('is_public', true)->where('active', true)->where(function ($q) use ($publicIds) {
            $q->whereNull('element_id')->orWhereIn('element_id', $publicIds);
        })->with('element')->get()->map(fn ($o) => [
            'uuid' => $o->uuid, 'name' => $o->name, 'audience' => $o->audience, 'element_uuid' => $o->element?->uuid, 'price_cents' => $o->price_cents,
            'price_version' => $o->price_version, 'seller' => $o->seller, 'description' => $o->content['description'] ?? '', 'included' => $o->content['included'] ?? '',
            'conditions' => $o->content['conditions'] ?? '', 'delivery' => $o->content['delivery'] ?? '',
        ])->all();
        $products = $scenario->products()->where('is_public', true)->where(function ($q) use ($publicIds) {
            $q->whereNull('element_id')->orWhereIn('element_id', $publicIds);
        })->get()->map(fn ($p) => [
            'id' => $p->id, 'sku' => $p->sku, 'name' => $p->name, 'price_cents' => $p->price_cents, 'element_id' => $p->element_id, 'seller' => $p->seller,
            'description' => $p->content['description'] ?? '',
        ])->all();

        return ['schema_version' => 1, 'event' => ['uuid' => $event->uuid, 'name' => $event->name, 'slug' => $event->slug,
            'description' => $description, 'venue' => $event->venue, 'starts_at' => $event->starts_at?->toIso8601String(), 'ends_at' => $event->ends_at?->toIso8601String(),
            'currency' => $event->currency, 'timezone' => $event->timezone, 'entry_free' => (bool) ($event->settings['entry_free'] ?? false),
            'phase' => $event->phase, 'decision' => $event->publicDecision(), 'phase_copy' => $phaseCopy,
            'faq' => $faq, 'is_demo' => $event->is_demo],
            'center' => [(float) $scenario->center_lat, (float) $scenario->center_lng],
            'plan' => ['type' => 'FeatureCollection', 'features' => $features], 'offers' => $offers, 'products' => $products];
    }

    public function publish(EventScenario $scenario): EventPublication
    {
        return DB::transaction(function () use ($scenario) {
            $event = PlannerEvent::query()->lockForUpdate()->findOrFail($scenario->event_id);
            $scenario = EventScenario::query()->lockForUpdate()->findOrFail($scenario->id);
            if ($event->phase === 'official') {
                abort_unless($event->decision === 'confirmed' && $event->confirmed_scenario_id === $scenario->id, 422, 'Publier le format confirmé.');
                $finance = app(EventFinance::class)->summarize($scenario);
                abort_unless($finance['can_confirm'], 422, 'Le dossier économique a changé : revoir le maintien avant publication officielle.');
            }
            $snapshot = $this->snapshot($scenario);
            $json = json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $revision = EventPublication::create(['event_id' => $event->id, 'scenario_id' => $scenario->id,
                'number' => 1 + (int) $event->publications()->max('number'), 'snapshot' => $snapshot, 'checksum' => hash('sha256', $json), 'admin_id' => auth('admin')->id()]);
            $event->update(['published_revision_id' => $revision->id]);

            return $revision;
        }, 3);
    }

    public function read(PlannerEvent $event): array
    {
        EventAccess::authorizePublic($event);
        $publication = $event->publications()->findOrFail($event->published_revision_id);
        $snapshot = $publication->snapshot;
        $snapshot['publication'] = ['number' => $publication->number, 'published_at' => $publication->created_at->toIso8601String()];
        $snapshot['event']['decision'] = $event->publicDecision();
        $snapshot['event']['decision_deadline'] = $event->decision_deadline?->toIso8601String();
        $snapshot['event']['status_changed'] = $snapshot['event']['phase'] !== $event->phase;
        $active = in_array($event->publicDecision(), ['pending', 'confirmed'], true) && in_array($event->phase, ['applications', 'official'], true) && ! $snapshot['event']['status_changed'];
        // The live decision controls the headline; publication geometry and approved texts stay immutable.
        $snapshot['event']['phase'] = $event->phase;
        $offers = EventOffer::query()->whereIn('uuid', array_column($snapshot['offers'], 'uuid'))->with('pool')->get()->keyBy('uuid');
        foreach ($snapshot['offers'] as &$offer) {
            $live = $offers->get($offer['uuid']);
            $offer['available'] = $active && $live?->active && $live->is_public && $live->price_version === $offer['price_version'];
            $offer['remaining'] = $offer['available'] ? $live->available() : 0;
            if ($offer['remaining'] === 0) {
                $offer['available'] = false;
            }
        }
        unset($offer);
        $products = EventProduct::query()->whereIn('id', array_column($snapshot['products'], 'id'))->get()->keyBy('id');
        foreach ($snapshot['products'] as &$product) {
            $live = $products->get($product['id']);
            $product['available'] = $active && $live?->is_public && $live->price_cents === $product['price_cents'] && $live->stock > 0;
            $product['remaining'] = $product['available'] ? $live->stock : 0;
        }
        unset($product);

        return $snapshot;
    }
}
