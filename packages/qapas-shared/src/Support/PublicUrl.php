<?php

namespace Qapas\Shared\Support;

final class PublicUrl
{
    public static function origin(mixed $value): ?string
    {
        $parts = self::parts($value);

        if ($parts === null || ! in_array($parts['path'] ?? '', ['', '/'], true)) {
            return null;
        }

        return rtrim($value, '/');
    }

    public static function destination(mixed $value): ?string
    {
        return self::parts($value) === null ? null : $value;
    }

    private static function parts(mixed $value): ?array
    {
        if (! is_string($value) || strlen($value) > 2048 || ! filter_var($value, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($value);

        if (! is_array($parts)
            || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || ! isset($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment'])
            || (! app()->environment('local', 'testing') && $parts['scheme'] !== 'https')) {
            return null;
        }

        return $parts;
    }
}

