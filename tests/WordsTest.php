<?php

namespace Djouamaah\AlgeriaInvoice\Tests;

use Djouamaah\AlgeriaInvoice\Enums\DocumentType;
use Djouamaah\AlgeriaInvoice\Words\AmountInWords;
use Djouamaah\AlgeriaInvoice\Words\ArabicNumbers;
use Djouamaah\AlgeriaInvoice\Words\FrenchNumbers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase as BaseTestCase;

class WordsTest extends BaseTestCase
{
    public static function french(): array
    {
        return [
            [0, 'zéro'], [1, 'un'], [16, 'seize'], [17, 'dix-sept'], [21, 'vingt et un'], [22, 'vingt-deux'],
            [71, 'soixante et onze'], [77, 'soixante-dix-sept'], [80, 'quatre-vingts'], [81, 'quatre-vingt-un'],
            [91, 'quatre-vingt-onze'], [99, 'quatre-vingt-dix-neuf'], [100, 'cent'], [101, 'cent un'],
            [200, 'deux cents'], [201, 'deux cent un'], [1000, 'mille'], [1001, 'mille un'], [2000, 'deux mille'],
            [80000, 'quatre-vingt mille'], [200000, 'deux cent mille'], [1250, 'mille deux cent cinquante'],
            [1000000, 'un million'], [2000000, 'deux millions'], [200000000, 'deux cents millions'],
            [1000000000, 'un milliard'], [2080500, 'deux millions quatre-vingt mille cinq cents'],
        ];
    }

    #[DataProvider('french')]
    public function test_french_numbers(int $n, string $words): void
    {
        $this->assertSame($words, FrenchNumbers::words($n));
    }

    public static function arabic(): array
    {
        // amount in DA => words
        return [
            [1, 'دينار جزائري واحد'],
            [2, 'ديناران جزائريان'],
            [3, 'ثلاثة دنانير جزائرية'],
            [10, 'عشرة دنانير جزائرية'],
            [11, 'أحد عشر دينارًا جزائريًا'],
            [21, 'واحد وعشرون دينارًا جزائريًا'],
            [100, 'مئة دينار جزائري'],
            [200, 'مئتا دينار جزائري'],
            [300, 'ثلاثمئة دينار جزائري'],
            [1000, 'ألف دينار جزائري'],
            [1200, 'ألف ومئتا دينار جزائري'],
            [2000, 'ألفا دينار جزائري'],
            [3000, 'ثلاثة آلاف دينار جزائري'],
            [25000, 'خمسة وعشرون ألف دينار جزائري'],
            [25300, 'خمسة وعشرون ألفًا وثلاثمئة دينار جزائري'],
            [200000, 'مئتا ألف دينار جزائري'],
            [1000000, 'مليون دينار جزائري'],
            [2000000, 'مليونا دينار جزائري'],
            [11000000, 'أحد عشر مليون دينار جزائري'],
        ];
    }

    #[DataProvider('arabic')]
    public function test_arabic_amounts(int $dinars, string $words): void
    {
        $this->assertSame($words, AmountInWords::arabic($dinars * 100));
    }

    public function test_amounts_with_centimes(): void
    {
        $this->assertSame('mille deux cent cinquante dinars algériens et cinquante centimes', AmountInWords::french(125050));
        $this->assertSame('ألف ومئتان وخمسون دينارًا جزائريًا وخمسون سنتيمًا', AmountInWords::arabic(125050));
        $this->assertSame('un dinar algérien et un centime', AmountInWords::french(101));
        $this->assertSame('دينار جزائري واحد وسنتيم واحد', AmountInWords::arabic(101));
        $this->assertSame('deux millions de dinars algériens', AmountInWords::french(200000000));
        $this->assertSame('zéro dinar algérien', AmountInWords::french(0));
        $this->assertSame('ثلاثة آلاف', ArabicNumbers::words(3000));
    }

    public function test_sentences(): void
    {
        $this->assertSame('Arrêtée la présente facture à la somme de : mille dinars algériens.', AmountInWords::sentence(100000));
        $this->assertSame('Arrêté le présent avoir à la somme de : mille dinars algériens.', AmountInWords::sentence(100000, 'fr', DocumentType::CreditNote));
        $this->assertSame('أُوقفت هذه الفاتورة على مبلغ: ألف دينار جزائري.', AmountInWords::sentence(100000, 'ar'));
    }
}
