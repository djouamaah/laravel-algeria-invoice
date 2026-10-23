<?php

namespace Djouamaah\AlgeriaInvoice;

use InvalidArgumentException;

/**
 * Amounts are integers in centimes (1 DA = 100 centimes) everywhere in the package.
 */
final class Money
{
    /**
     * 1250.5 or "1250.50" (DA) -> 125050 (centimes).
     */
    public static function toCentimes(int|float|string $dinars): int
    {
        if (is_string($dinars)) {
            $dinars = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], $dinars);
            if (! is_numeric($dinars)) {
                throw new InvalidArgumentException("Invalid amount [{$dinars}].");
            }
        }

        return (int) round(((float) $dinars) * 100);
    }

    public static function toDinars(int $centimes): float
    {
        return $centimes / 100;
    }

    /**
     * 125050 -> "1 250,50 DA" (fr) or "1.250,50 دج" (ar).
     */
    public static function format(int $centimes, string $locale = 'fr', bool $withCurrency = true): string
    {
        $negative = $centimes < 0;
        $centimes = abs($centimes);
        $thousands = str_starts_with($locale, 'ar') ? '.' : "\u{202F}";
        $value = number_format(intdiv($centimes, 100), 0, '', $thousands).','.str_pad((string) ($centimes % 100), 2, '0', STR_PAD_LEFT);
        $value = ($negative ? '-' : '').$value;

        if (! $withCurrency) {
            return $value;
        }

        return str_starts_with($locale, 'ar') ? $value.' دج' : $value.' DA';
    }
}
