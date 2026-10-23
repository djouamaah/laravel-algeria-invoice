<?php

namespace Djouamaah\AlgeriaInvoice\Tests;

use Djouamaah\AlgeriaInvoice\Enums\PaymentMethod;
use Djouamaah\AlgeriaInvoice\Facades\AlgeriaInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class NumberingAndRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequences_are_gap_free_per_series_and_year(): void
    {
        $this->assertSame('FA-2026-00001', AlgeriaInvoice::nextNumber(year: 2026));
        $this->assertSame('FA-2026-00002', AlgeriaInvoice::nextNumber(year: 2026));
        $this->assertSame('AV-2026-00001', AlgeriaInvoice::nextNumber('credit_note', 2026));
        $this->assertSame('FA-2027-00001', AlgeriaInvoice::nextNumber(year: 2027));
        $this->assertSame(2, AlgeriaInvoice::sequence()->current(year: 2026));
    }

    public function test_number_is_not_consumed_when_saving_fails(): void
    {
        AlgeriaInvoice::nextNumber(year: 2026);

        try {
            DB::transaction(function () {
                AlgeriaInvoice::nextNumber(year: 2026);
                throw new RuntimeException('saving the invoice failed');
            });
        } catch (RuntimeException) {
        }

        $this->assertSame('FA-2026-00002', AlgeriaInvoice::nextNumber(year: 2026));
    }

    public function test_custom_format(): void
    {
        config(['algeria-invoice.numbering.format' => '{number}/{year}', 'algeria-invoice.numbering.pad' => 3]);
        $this->app->forgetInstance(\Djouamaah\AlgeriaInvoice\AlgeriaInvoice::class);
        AlgeriaInvoice::clearResolvedInstances();

        $this->assertSame('001/2026', AlgeriaInvoice::nextNumber(year: 2026));
    }

    protected function invoice()
    {
        return AlgeriaInvoice::make()
            ->number('FA-2026-00042')
            ->date('2026-10-23')
            ->buyer(self::companyBuyer())
            ->addLine('Ordinateur portable', 2, 85000, 19)
            ->addLine('Livre scolaire', 3, 1200, 9)
            ->payment(PaymentMethod::Cash);
    }

    public function test_html_in_french_and_arabic(): void
    {
        $fr = AlgeriaInvoice::html($this->invoice(), 'fr');
        $this->assertStringContainsString('<html lang="fr" dir="ltr">', $fr);
        $this->assertStringContainsString('FA-2026-00042', $fr);
        $this->assertStringContainsString('Droit de timbre', $fr);
        $this->assertStringContainsString('Arrêtée la présente facture à la somme de', $fr);
        $this->assertStringContainsString('000116123456789', $fr);

        $ar = AlgeriaInvoice::html($this->invoice(), 'ar');
        $this->assertStringContainsString('<html lang="ar" dir="rtl">', $ar);
        $this->assertStringContainsString('فاتورة', $ar);
        $this->assertStringContainsString('حقوق الطابع', $ar);
        $this->assertStringContainsString('أُوقفت هذه الفاتورة على مبلغ', $ar);

        // the application locale is restored
        $this->assertSame('en', app()->getLocale());
    }

    public function test_pdf(): void
    {
        if (! class_exists(\Mpdf\Mpdf::class)) {
            $this->markTestSkipped('mpdf/mpdf is not installed.');
        }

        $pdf = AlgeriaInvoice::pdf($this->invoice(), 'ar');
        $this->assertStringStartsWith('%PDF', $pdf);

        $response = AlgeriaInvoice::download($this->invoice());
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('FA-2026-00042.pdf', $response->headers->get('Content-Disposition'));
    }
}
