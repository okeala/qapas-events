<?php

namespace App\Services\Events;

use Illuminate\Validation\ValidationException;

class Money
{
    public static function parse(string|int|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = str_replace([' ', "\u{00a0}"], '', (string) $value);
        if (! preg_match('/^(\d{1,10})(?:[.,](\d{1,2}))?$/D', $value, $m)) {
            throw ValidationException::withMessages(['amount' => 'Saisir un montant positif avec deux décimales maximum.']);
        }

        return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '', 2, '0');
    }

    public static function input(?int $cents): string
    {
        return $cents === null ? '' : number_format($cents / 100, 2, '.', '');
    }

    public static function display(?int $cents, string $currency = 'EUR'): string
    {
        return $cents === null ? 'À chiffrer' : number_format($cents / 100, 2, ',', ' ').' '.$currency;
    }
}
