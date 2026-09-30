<?php

namespace Qapas\Shared\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

final class QapasChrome
{
    public static function platformUrl(): ?string
    {
        return PublicUrl::origin(config('qapas_application.platform_url'));
    }

    public static function manifest(): array
    {
        $origin = self::platformUrl();
        $fallback = self::fallback($origin);
        if ($origin === null) {
            return $fallback;
        }

        $key = 'qapas:chrome:v2:'.hash('sha256', $origin.'|'.config('qapas_application.id'));

        $manifest = Cache::remember($key, now()->addMinutes(5), function () use ($origin, $key, $fallback): array {
            try {
                $response = Http::acceptJson()->withoutRedirecting()->timeout(2)
                    ->get($origin.'/navigation/v1.json');

                if ($response->successful() && $response->json('version') === 1) {
                    $applications = self::applications($response->json('applications'), $origin);
                    $footer = self::footer($response->json('footer'), $origin);
                    $events = self::events($response->json('events'), $origin);
                    if ($applications !== null && $footer !== null && $events !== null) {
                        $manifest = ['applications' => $applications, 'footer' => $footer, 'events' => $events];
                        Cache::put($key.':last-good', $manifest, now()->addDay());

                        return $manifest;
                    }
                }
            } catch (Throwable) {
                // Public QAPAS links remain available without granting access.
            }

            return Cache::get($key.':last-good', $fallback);
        });

        // Treat cached data as untrusted: older schemas and partial entries must
        // never reach the navigation or footer views unchecked.
        if (! is_array($manifest)) {
            return $fallback;
        }

        return [
            'applications' => self::applications($manifest['applications'] ?? null, $origin) ?? $fallback['applications'],
            'footer' => self::footer($manifest['footer'] ?? null, $origin) ?? $fallback['footer'],
            'events' => self::events($manifest['events'] ?? null, $origin) ?? $fallback['events'],
        ];
    }

    public static function labels(mixed $value, int $maxBytes): bool
    {
        if (! is_array($value)) {
            return false;
        }

        foreach (['fr', 'en', 'pt'] as $required) {
            if (! is_string($value[$required] ?? null) || trim($value[$required]) === '') {
                return false;
            }
        }

        foreach ($value as $locale => $label) {
            if (! in_array($locale, config('qapas_application.locales'), true)
                || ! is_string($label) || trim($label) === '' || strlen($label) > $maxBytes) {
                return false;
            }
        }

        return true;
    }

    private static function applications(mixed $value, string $origin): ?array
    {
        if (! is_array($value) || count($value) > 20) {
            return null;
        }

        $seen = [];
        $applications = [];
        $ownId = config('qapas_application.id');
        foreach ($value as $entry) {
            if (! is_array($entry) || ! is_string($entry['id'] ?? null)
                || ! preg_match('/^[a-z][a-z0-9_-]{0,30}$/', $entry['id'])
                || isset($seen[$entry['id']])
                || ! in_array($entry['status'] ?? null, ['available', 'unavailable', 'coming_soon'], true)
                || ! self::labels($entry['label'] ?? null, 100)) {
                return null;
            }

            $url = isset($entry['url']) ? PublicUrl::destination($entry['url']) : null;
            if ($entry['id'] === 'platform' && $url !== $origin.'/overview') {
                return null;
            }
            if (($entry['status'] === 'available' && $url === null)
                || ($entry['status'] !== 'available' && $url !== null)) {
                return null;
            }

            $seen[$entry['id']] = true;
            $applications[] = [
                'id' => $entry['id'], 'label' => $entry['label'],
                'status' => $entry['status'],
                'url' => $entry['id'] === $ownId ? route('home') : $url,
            ];
        }

        if (! isset($seen['platform'])) {
            return null;
        }
        if (! isset($seen[$ownId])) {
            $applications[] = self::currentApplication();
        }

        return $applications;
    }

    private static function footer(mixed $value, string $origin): ?array
    {
        if (! is_array($value) || ($value['version'] ?? null) !== 1
            || ! is_array($value['links'] ?? null) || count($value['links']) > 6
            || ! self::labels($value['note'] ?? null, 180)) {
            return null;
        }

        $paths = [
            'applications' => '/overview#applications', 'help' => '/help',
            'requirements' => '/cahier-des-charges', 'policies' => '/policies',
        ];
        $seen = [];
        $links = [];
        foreach ($value['links'] as $entry) {
            if (! is_array($entry)) {
                return null;
            }
            $id = $entry['id'] ?? null;
            if (! is_string($id) || ! isset($paths[$id])
                || isset($seen[$id]) || ! self::labels($entry['label'] ?? null, 100)
                || ($entry['url'] ?? null) !== $origin.$paths[$id]) {
                return null;
            }
            $seen[$id] = true;
            $links[] = $entry;
        }

        return ['version' => 1, 'links' => $links, 'note' => $value['note']];
    }

    private static function events(mixed $value, string $origin): ?array
    {
        if ($value === null) {
            return self::fallbackEvents($origin);
        }
        if (! is_array($value) || ! is_int($value['year'] ?? null)
            || $value['year'] < 2020 || $value['year'] > 2100
            || ! is_array($value['items'] ?? null) || count($value['items']) > 12
            || ($value['index_url'] ?? null) !== $origin.'/events/'.$value['year']
            || ($value['games_url'] ?? null) !== $origin.'/applications/jogos') {
            return null;
        }
        foreach (['label', 'empty', 'all', 'games'] as $key) {
            if (! self::labels($value[$key] ?? null, 180) || count($value[$key]) !== 6) {
                return null;
            }
        }
        $seen = [];
        foreach ($value['items'] as $item) {
            if (! is_array($item) || ! is_string($item['slug'] ?? null)
                || ! preg_match('/^[a-z][a-z0-9-]{0,63}$/', $item['slug'])
                || isset($seen[$item['slug']])
                || ! self::labels($item['title'] ?? null, 140) || count($item['title']) !== 6
                || ! is_string($item['date'] ?? null)
                || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $item['date'])
                || ! checkdate((int) substr($item['date'], 5, 2), (int) substr($item['date'], 8, 2), (int) substr($item['date'], 0, 4))
                || (int) substr($item['date'], 0, 4) !== $value['year']
                || ($item['url'] ?? null) !== $origin.'/events/'.$value['year'].'/'.$item['slug']) {
                return null;
            }
            $seen[$item['slug']] = true;
        }

        return $value;
    }

    private static function fallbackEvents(?string $origin): array
    {
        $year = (int) now()->year;

        return [
            'year' => $year,
            'label' => config('qapas_events.label'), 'empty' => config('qapas_events.empty'),
            'all' => config('qapas_events.all'), 'games' => config('qapas_events.games'),
            'index_url' => $origin ? $origin.'/events/'.$year : null,
            'games_url' => $origin ? $origin.'/applications/jogos' : null,
            'items' => [],
        ];
    }

    private static function fallback(?string $origin): array
    {
        $labels = ['fr' => 'Vue d’ensemble', 'en' => 'Overview', 'pt' => 'Visão geral',
            'nl' => 'Overzicht', 'es' => 'Vista general', 'de' => 'Überblick'];
        $linkLabels = [
            'applications' => ['fr' => 'Applications', 'en' => 'Applications', 'pt' => 'Aplicações', 'nl' => 'Toepassingen', 'es' => 'Aplicaciones', 'de' => 'Anwendungen'],
            'help' => ['fr' => 'Aide', 'en' => 'Help', 'pt' => 'Ajuda', 'nl' => 'Hulp', 'es' => 'Ayuda', 'de' => 'Hilfe'],
            'requirements' => ['fr' => 'Projet', 'en' => 'Project', 'pt' => 'Projeto', 'nl' => 'Project', 'es' => 'Proyecto', 'de' => 'Projekt'],
            'policies' => ['fr' => 'Règles', 'en' => 'Policies', 'pt' => 'Políticas', 'nl' => 'Beleid', 'es' => 'Políticas', 'de' => 'Richtlinien'],
        ];
        $links = [];
        foreach (['applications' => '/overview#applications', 'help' => '/help',
            'requirements' => '/cahier-des-charges', 'policies' => '/policies'] as $id => $path) {
            if ($origin !== null) {
                $links[] = ['id' => $id, 'url' => $origin.$path, 'label' => $linkLabels[$id]];
            }
        }

        return [
            'applications' => [
                ['id' => 'platform', 'label' => $labels, 'url' => $origin ? $origin.'/overview' : null,
                    'status' => $origin ? 'available' : 'unavailable'],
                self::currentApplication(),
            ],
            'footer' => ['links' => $links,
                'note' => ['fr' => 'Chaque application protège ses propres données.',
                    'en' => 'Each application protects its own data.', 'pt' => 'Cada aplicação protege os seus próprios dados.',
                    'nl' => 'Elke toepassing beschermt haar eigen gegevens.', 'es' => 'Cada aplicación protege sus propios datos.',
                    'de' => 'Jede Anwendung schützt ihre eigenen Daten.']],
            'events' => self::fallbackEvents($origin),
        ];
    }

    private static function currentApplication(): array
    {
        return ['id' => config('qapas_application.id'), 'label' => config('qapas_application.title'),
            'status' => 'available', 'url' => route('home')];
    }
}
