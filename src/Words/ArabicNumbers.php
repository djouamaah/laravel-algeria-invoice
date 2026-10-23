<?php

namespace Djouamaah\AlgeriaInvoice\Words;

use InvalidArgumentException;

/**
 * Cardinal numbers in Arabic words, for a masculine counted noun (دينار، سنتيم):
 * 3 to 10 take the feminine form (ثلاثة دنانير), 11 to 99 a singular accusative noun (أحد عشر دينارًا).
 */
final class ArabicNumbers
{
    private const ONES = [1 => 'واحد', 2 => 'اثنان', 3 => 'ثلاثة', 4 => 'أربعة', 5 => 'خمسة', 6 => 'ستة',
        7 => 'سبعة', 8 => 'ثمانية', 9 => 'تسعة', 10 => 'عشرة'];

    private const TEENS = [11 => 'أحد عشر', 12 => 'اثنا عشر', 13 => 'ثلاثة عشر', 14 => 'أربعة عشر', 15 => 'خمسة عشر',
        16 => 'ستة عشر', 17 => 'سبعة عشر', 18 => 'ثمانية عشر', 19 => 'تسعة عشر'];

    private const TENS = [2 => 'عشرون', 3 => 'ثلاثون', 4 => 'أربعون', 5 => 'خمسون', 6 => 'ستون', 7 => 'سبعون',
        8 => 'ثمانون', 9 => 'تسعون'];

    private const HUNDREDS = [1 => 'مئة', 2 => 'مئتان', 3 => 'ثلاثمئة', 4 => 'أربعمئة', 5 => 'خمسمئة', 6 => 'ستمئة',
        7 => 'سبعمئة', 8 => 'ثمانمئة', 9 => 'تسعمئة'];

    /** [singular, dual, plural (3-10), accusative singular (11-99)] */
    private const SCALES = [
        1_000_000_000 => ['مليار', 'ملياران', 'مليارات', 'مليارًا'],
        1_000_000 => ['مليون', 'مليونان', 'ملايين', 'مليونًا'],
        1000 => ['ألف', 'ألفان', 'آلاف', 'ألفًا'],
    ];

    public static function words(int $n): string
    {
        if ($n < 0 || $n >= 1_000_000_000_000) {
            throw new InvalidArgumentException('Only numbers from 0 to 999 999 999 999 are supported.');
        }
        if ($n === 0) {
            return 'صفر';
        }

        $parts = [];
        foreach (self::SCALES as $scale => $forms) {
            $group = intdiv($n, $scale) % 1000;
            if ($group) {
                $parts[] = self::counted($group, $forms, true);
            }
        }
        if ($n % 1000) {
            $parts[] = self::hundreds($n % 1000);
        }

        return implode(' و', $parts);
    }

    /**
     * A number followed by its counted noun in the right form.
     *
     * @param  array{0: string, 1: string, 2: string, 3: string}  $forms  [singular, dual, plural, accusative]
     * @param  bool  $scale  "ألف" alone for 1 (a currency says "دينار واحد" instead, see AmountInWords)
     */
    public static function counted(int $n, array $forms, bool $scale = false): string
    {
        if ($n === 1) {
            return $scale ? $forms[0] : $forms[0].' واحد';
        }
        if ($n === 2) {
            return $forms[1];
        }

        $rest = $n % 100;
        $noun = match (true) {
            $rest >= 3 && $rest <= 10 => $forms[2],
            $rest >= 11 => $forms[3],
            default => $forms[0],   // 100, 200, 101, 102 ... : genitive singular
        };

        $number = self::words($n);
        if ($noun === $forms[0]) {
            $number = self::construct($number);
        }

        return $number.' '.$noun;
    }

    /**
     * Before a genitive noun the last word takes its construct form: مئتا دينار، ألفا دينار، خمسة وعشرون ألف دينار.
     */
    private static function construct(string $number): string
    {
        foreach (['مئتان' => 'مئتا', 'ألفان' => 'ألفا', 'مليونان' => 'مليونا', 'ملياران' => 'مليارا',
            'ألفًا' => 'ألف', 'مليونًا' => 'مليون', 'مليارًا' => 'مليار'] as $from => $to) {
            if (str_ends_with($number, $from)) {
                return substr($number, 0, -strlen($from)).$to;
            }
        }

        return $number;
    }

    private static function hundreds(int $n): string
    {
        $parts = [];
        if (intdiv($n, 100)) {
            $parts[] = self::HUNDREDS[intdiv($n, 100)];
        }

        $rest = $n % 100;
        if ($rest) {
            $parts[] = match (true) {
                $rest <= 10 => self::ONES[$rest],
                $rest < 20 => self::TEENS[$rest],
                $rest % 10 === 0 => self::TENS[intdiv($rest, 10)],
                default => self::ONES[$rest % 10].' و'.self::TENS[intdiv($rest, 10)],
            };
        }

        return implode(' و', $parts);
    }
}
