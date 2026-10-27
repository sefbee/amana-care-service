<?php

namespace App\Admin\Services;

use Illuminate\Validation\ValidationException;

class FinanceMoney
{
    public static function cents(string|int|float $amount): int
    {
        $value = trim((string) $amount);
        if (! preg_match('/^(\d{1,10})(?:\.(\d{1,2}))?$/', $value, $matches)) {
            throw ValidationException::withMessages(['amount' => 'Amounts must fit the supported decimal precision of 12,2.']);
        }

        $whole = (int) $matches[1];
        $fraction = (int) str_pad($matches[2] ?? '', 2, '0');
        $cents = ($whole * 100) + $fraction;
        if ($cents > 999999999999) {
            throw ValidationException::withMessages(['amount' => 'Amount exceeds the supported maximum.']);
        }

        return $cents;
    }

    public static function decimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
