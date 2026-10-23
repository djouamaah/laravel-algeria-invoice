<?php

namespace Djouamaah\AlgeriaInvoice\Words;

use Djouamaah\AlgeriaInvoice\Enums\DocumentType;

/**
 * Amounts in Algerian dinars written in French or Arabic words, as required at the bottom of an invoice.
 */
final class AmountInWords
{
    private const DINAR_AR = ['دينار جزائري', 'ديناران جزائريان', 'دنانير جزائرية', 'دينارًا جزائريًا'];

    private const CENTIME_AR = ['سنتيم', 'سنتيمان', 'سنتيمات', 'سنتيمًا'];

    /**
     * 125050 -> "mille deux cent cinquante dinars algériens et cinquante centimes"
     */
    public static function french(int $centimes): string
    {
        $centimes = abs($centimes);
        $dinars = intdiv($centimes, 100);
        $cents = $centimes % 100;

        // "un million de dinars", but "un million deux cents dinars"
        $of = $dinars >= 1_000_000 && $dinars % 1_000_000 === 0 ? ' de' : '';
        $text = $dinars <= 1
            ? FrenchNumbers::words($dinars).' dinar algérien'
            : FrenchNumbers::words($dinars).$of.' dinars algériens';

        if ($cents) {
            $text .= ' et '.FrenchNumbers::words($cents).' centime'.($cents > 1 ? 's' : '');
        }

        return $text;
    }

    /**
     * 125050 -> "ألف ومئتان وخمسون دينارًا جزائريًا وخمسون سنتيمًا"
     */
    public static function arabic(int $centimes): string
    {
        $centimes = abs($centimes);
        $dinars = intdiv($centimes, 100);
        $cents = $centimes % 100;

        $text = $dinars === 0 ? 'صفر دينار جزائري' : ArabicNumbers::counted($dinars, self::DINAR_AR);
        if ($dinars === 1) {
            $text = 'دينار جزائري واحد';
        }

        if ($cents) {
            $text .= ' و'.ArabicNumbers::counted($cents, self::CENTIME_AR);
        }

        return $text;
    }

    public static function words(int $centimes, string $locale = 'fr'): string
    {
        return str_starts_with($locale, 'ar') ? self::arabic($centimes) : self::french($centimes);
    }

    /**
     * The closing sentence: "Arrêtée la présente facture à la somme de : ..."
     */
    public static function sentence(int $centimes, string $locale = 'fr', DocumentType $type = DocumentType::Invoice): string
    {
        if (str_starts_with($locale, 'ar')) {
            $document = match ($type) {
                DocumentType::Invoice => 'أُوقفت هذه الفاتورة',
                DocumentType::CreditNote => 'أُوقف هذا الإشعار الدائن',
                DocumentType::Proforma => 'أُوقفت هذه الفاتورة الشكلية',
            };

            return $document.' على مبلغ: '.self::arabic($centimes).'.';
        }

        $document = match ($type) {
            DocumentType::Invoice => 'Arrêtée la présente facture',
            DocumentType::CreditNote => 'Arrêté le présent avoir',
            DocumentType::Proforma => 'Arrêtée la présente facture proforma',
        };

        return $document.' à la somme de : '.self::french($centimes).'.';
    }
}
