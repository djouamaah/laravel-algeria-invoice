<?php

namespace Djouamaah\AlgeriaInvoice\Tests;

use Djouamaah\AlgeriaInvoice\Money;
use Djouamaah\AlgeriaInvoice\Tax\StampDuty;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase as BaseTestCase;

class StampDutyTest extends BaseTestCase
{
    public static function wholeAmounts(): array
    {
        // amount (DA) => stamp duty (DA), Finance Law 2025 defaults
        return [
            'zero' => [0, 0],
            'exempt up to 300 DA' => [300, 0],
            'minimum 5 DA' => [301, 5],
            'started slice counts' => [1050, 11],
            '1% up to 30 000 DA' => [30000, 300],
            '1.5% above 30 000 DA' => [30000.01, 451.5],
            '1.5% at 50 000 DA' => [50000, 750],
            '1.5% at 100 000 DA' => [100000, 1500],
            '2% above 100 000 DA' => [150000, 3000],
            'no cap' => [10000000, 200000],
        ];
    }

    #[DataProvider('wholeAmounts')]
    public function test_whole_mode(float $amount, float $expected): void
    {
        $this->assertSame(Money::toCentimes($expected), (new StampDuty)->calculate(Money::toCentimes($amount)));
    }

    public function test_marginal_mode(): void
    {
        $stamp = new StampDuty(['mode' => 'marginal', 'exempt_up_to' => 300, 'minimum' => 5]);

        // 30 000 at 1 + 70 000 at 1.5 + 50 000 at 2
        $this->assertSame(Money::toCentimes(300 + 1050 + 1000), $stamp->calculate(Money::toCentimes(150000)));
        $this->assertSame(Money::toCentimes(300), $stamp->calculate(Money::toCentimes(30000)));
    }

    public function test_configurable_minimum_maximum_and_threshold(): void
    {
        $stamp = new StampDuty(['minimum' => 30, 'maximum' => 2500, 'exempt_up_to' => 0]);

        $this->assertSame(3000, $stamp->calculate(Money::toCentimes(250)));
        $this->assertSame(250000, $stamp->calculate(Money::toCentimes(10000000)));
    }

    public function test_rejects_invalid_configuration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StampDuty(['brackets' => [[30000, 1.0]]]);
    }
}
