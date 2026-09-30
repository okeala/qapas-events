<?php

namespace Qapas\Shared\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

final class QapasJourneys
{
    private const JOURNEYS = ['producer', 'participant', 'buyer', 'professional'];

    public static function selected(Request $request): ?string
    {
        if (config('qapas_application.local_prisms') !== null) {
            return LocalPrisms::selected($request);
        }

        $id = $request->query('profil');

        return is_string($id) && in_array($id, self::JOURNEYS, true) ? $id : null;
    }

    public static function manifest(): ?array
    {
        if (config('qapas_application.local_prisms') !== null) {
            return LocalPrisms::manifest();
        }

        $origin = QapasChrome::platformUrl();
        if ($origin === null) {
            return null;
        }

        $key = 'qapas:journeys:v1:'.hash('sha256', $origin);

        return Cache::remember($key, now()->addMinutes(5), function () use ($origin, $key): ?array {
            try {
                $response = Http::acceptJson()->withoutRedirecting()->timeout(2)->get($origin.'/journeys/v1.json');
                if ($response->successful() && ($validated = self::validated($response->json(), $origin)) !== null) {
                    Cache::put($key.':last-good', $validated, now()->addDay());

                    return $validated;
                }
            } catch (Throwable) {
                // The public page remains usable when Platform is offline.
            }

            return Cache::get($key.':last-good');
        });
    }

    private static function validated(mixed $value, string $origin): ?array
    {
        if (! is_array($value) || ($value['version'] ?? null) !== 1
            || ! is_array($value['journeys'] ?? null) || count($value['journeys']) !== 4
            || ! self::localized($value['heading'] ?? null, 120)
            || ! self::localized($value['lead'] ?? null, 320)
            || ! self::localized($value['change'] ?? null, 100)) {
            return null;
        }

        foreach (self::JOURNEYS as $index => $id) {
            $entry = $value['journeys'][$index] ?? null;
            if (! is_array($entry) || ($entry['id'] ?? null) !== $id
                || ! self::localized($entry['title'] ?? null, 140)
                || ! self::localized($entry['description'] ?? null, 320)
                || ($entry['overview_url'] ?? null) !== $origin.'/overview?profil='.$id) {
                return null;
            }
        }

        return [
            'heading' => $value['heading'], 'lead' => $value['lead'], 'change' => $value['change'],
            'journeys' => array_map(fn ($entry) => [
                'id' => $entry['id'], 'title' => $entry['title'],
                'description' => $entry['description'], 'overview_url' => $entry['overview_url'],
            ], $value['journeys']),
        ];
    }

    private static function localized(mixed $labels, int $maxBytes): bool
    {
        if (! is_array($labels) || count($labels) !== 6) {
            return false;
        }

        foreach (config('qapas_application.locales') as $locale) {
            if (! is_string($labels[$locale] ?? null) || trim($labels[$locale]) === ''
                || strlen($labels[$locale]) > $maxBytes) {
                return false;
            }
        }

        return true;
    }
}

