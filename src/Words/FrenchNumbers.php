<?php

namespace Djouamaah\AlgeriaInvoice\Words;

use InvalidArgumentException;

/**
 * Cardinal numbers in French words, traditional spelling (vingt et un, quatre-vingts, deux cents).
 */
final class FrenchNumbers
{
    private const UNITS = ['zéro', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix',
        'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];

    private const TENS = [2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante'];

    public static function words(int $n): string
    {
        if ($n < 0 || $n >= 1_000_000_000_000) {
            throw new InvalidArgumentException('Only numbers from 0 to 999 999 999 999 are supported.');
        }
        if ($n === 0) {
            return 'zéro';
        }

        $parts = [];
        $billions = intdiv($n, 1_000_000_000);
        $millions = intdiv($n, 1_000_000) % 1000;
        $thousands = intdiv($n, 1000) % 1000;
        $rest = $n % 1000;

        if ($billions) {
            $parts[] = self::hundreds($billions, true).' milliard'.($billions > 1 ? 's' : '');
        }
        if ($millions) {
            $parts[] = self::hundreds($millions, true).' million'.($millions > 1 ? 's' : '');
        }
        if ($thousands) {
            // "mille" is invariable and never preceded by "un"; cent/vingt stay singular before it
            $parts[] = $thousands === 1 ? 'mille' : self::hundreds($thousands, false).' mille';
        }
        if ($rest) {
            $parts[] = self::hundreds($rest, true);
        }

        return implode(' ', $parts);
    }

    /**
     * @param  bool  $plural  whether "cents" / "quatre-vingts" may take an s (not before "mille")
     */
    private static function hundreds(int $n, bool $plural): string
    {
        $h = intdiv($n, 100);
        $r = $n % 100;
        $words = [];

        if ($h === 1) {
            $words[] = 'cent';
        } elseif ($h > 1) {
            $words[] = self::UNITS[$h].' cent'.($r === 0 && $plural ? 's' : '');
        }
        if ($r) {
            $words[] = self::belowHundred($r, $plural);
        }

        return implode(' ', $words);
    }

    private static function belowHundred(int $n, bool $plural): string
    {
        if ($n < 20) {
            return self::UNITS[$n];
        }
        if ($n < 70) {
            $tens = self::TENS[intdiv($n, 10)];
            $unit = $n % 10;

            return match ($unit) {
                0 => $tens,
                1 => $tens.' et un',
                default => $tens.'-'.self::UNITS[$unit],
            };
        }
        if ($n < 80) {
            return $n === 71 ? 'soixante et onze' : 'soixante-'.self::UNITS[$n - 60];
        }
        if ($n === 80) {
            return $plural ? 'quatre-vingts' : 'quatre-vingt';
        }

        return 'quatre-vingt-'.self::UNITS[$n - 80];
    }
}
