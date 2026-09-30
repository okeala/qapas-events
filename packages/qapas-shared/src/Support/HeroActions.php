<?php

namespace Qapas\Shared\Support;

use Illuminate\Support\Facades\Route;

final class HeroActions
{
    public static function valid(mixed $action): bool
    {
        if (! is_array($action) || ! self::labels($action['label'] ?? null)) {
            return false;
        }

        if (($action['destination'] ?? null) === 'platform_home') {
            return ! isset($action['route']) && ! isset($action['fragment']);
        }

        if (($action['destination'] ?? null) !== 'local_route'
            || ! is_string($action['route'] ?? null)
            || ! in_array($action['route'], config('qapas_application.hero_routes', []), true)
            || ! Route::has($action['route'])) {
            return false;
        }

        $fragment = $action['fragment'] ?? null;

        return $fragment === null || (is_string($fragment) && preg_match('/^[a-z][a-z0-9-]{0,63}$/', $fragment) === 1);
    }

    public static function url(array $action, string $locale): ?string
    {
        if (! self::valid($action)) {
            return null;
        }

        if ($action['destination'] === 'platform_home') {
            $origin = QapasChrome::platformUrl();

            return $origin ? $origin.'?lang='.$locale : null;
        }

        $url = route($action['route']);

        return $url.(isset($action['fragment']) ? '#'.$action['fragment'] : '');
    }

    private static function labels(mixed $labels): bool
    {
        if (! QapasChrome::labels($labels, 100) || count($labels) !== 6) {
            return false;
        }

        foreach (config('qapas_application.locales') as $locale) {
            if (! isset($labels[$locale])) {
                return false;
            }
        }

        return true;
    }
}
