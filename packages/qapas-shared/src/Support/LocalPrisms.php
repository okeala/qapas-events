<?php

namespace Qapas\Shared\Support;

use Illuminate\Http\Request;

final class LocalPrisms
{
    public static function selected(Request $request): ?string
    {
        $id = $request->query('profil');
        $manifest = self::manifest();

        return is_string($id) && collect($manifest['journeys'] ?? [])->contains(
            fn ($entry) => $entry['id'] === $id
        ) ? $id : null;
    }

    public static function manifest(): ?array
    {
        $value = config('qapas_application.local_prisms');
        if ($value === null) {
            return null;
        }

        abort_unless(is_array($value) && self::localized($value['heading'] ?? null, 120)
            && self::localized($value['lead'] ?? null, 320)
            && self::localized($value['change'] ?? null, 100)
            && is_array($value['journeys'] ?? null)
            && count($value['journeys']) >= 2 && count($value['journeys']) <= 8, 503);

        $seen = [];
        $journeys = [];
        foreach ($value['journeys'] as $entry) {
            abort_unless(is_array($entry), 503);
            $id = $entry['id'] ?? null;
            $action = ['destination' => 'local_route', 'route' => $entry['route'] ?? null,
                'fragment' => $entry['fragment'] ?? null, 'label' => $entry['title'] ?? null];
            abort_unless(is_string($id) && preg_match('/^[a-z][a-z0-9-]{0,30}$/', $id)
                && ! isset($seen[$id])
                && self::localized($entry['title'] ?? null, 140)
                && self::localized($entry['description'] ?? null, 320)
                && HeroActions::valid($action), 503);
            $seen[$id] = true;
            $journeys[] = [
                'id' => $id, 'title' => $entry['title'], 'description' => $entry['description'],
                'local_url' => HeroActions::url($action, app()->getLocale()),
                'overview_url' => null,
            ];
        }

        return [
            'heading' => $value['heading'], 'lead' => $value['lead'],
            'change' => $value['change'], 'journeys' => $journeys,
        ];
    }

    private static function localized(mixed $labels, int $limit): bool
    {
        if (! is_array($labels) || count($labels) !== 6) {
            return false;
        }

        foreach (config('qapas_application.locales') as $locale) {
            if (! is_string($labels[$locale] ?? null) || trim($labels[$locale]) === ''
                || strlen($labels[$locale]) > $limit) {
                return false;
            }
        }

        return true;
    }
}

