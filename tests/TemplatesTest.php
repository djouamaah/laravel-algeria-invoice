<?php

namespace Djouamaah\AlgeriaInvoice\Tests;

use Djouamaah\AlgeriaInvoice\Enums\PaymentMethod;
use Djouamaah\AlgeriaInvoice\Facades\AlgeriaInvoice;
use Djouamaah\AlgeriaInvoice\Invoice;
use Illuminate\Support\Facades\View;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

class TemplatesTest extends TestCase
{
    protected function invoice(): Invoice
    {
        return AlgeriaInvoice::make()
            ->number('FA-2026-00042')
            ->date('2026-10-23')
            ->orderReference('BC-118')
            ->buyer(self::companyBuyer())
            ->addLine('Ordinateur portable', 2, 85000, 19, 'pièce')
            ->addLine('Livre scolaire', 30, 1200, 9, 'pièce', 5)
            ->addLine('Formation', 1, 15000, 0)
            ->payment(PaymentMethod::Cash)
            ->notes('Livraison incluse');
    }

    public static function templates(): array
    {
        return [['classic'], ['modern'], ['minimal']];
    }

    #[DataProvider('templates')]
    public function test_builtin_template_renders_everything(string $template): void
    {
        $fr = AlgeriaInvoice::html($this->invoice(), 'fr', $template);

        $this->assertStringContainsString('<html lang="fr" dir="ltr">', $fr);
        foreach (['Facture', 'FA-2026-00042', 'BC-118', '23/10/2026', 'SARL Exemple Informatique', 'EURL Client Pro',
            '000116123456789', '000219987654321', 'Ordinateur portable', 'Remise', 'TVA 19 %', 'TVA 9 %', 'Exonéré de TVA',
            'Droit de timbre', 'Net à payer', 'Espèces', 'Livraison incluse', 'Arrêtée la présente facture'] as $text) {
            $this->assertStringContainsString($text, $fr, "$template (fr): $text");
        }

        $ar = AlgeriaInvoice::html($this->invoice(), 'ar', $template);
        $this->assertStringContainsString('<html lang="ar" dir="rtl">', $ar);
        foreach (['فاتورة', 'حقوق الطابع', 'الصافي للدفع', 'نقدًا', 'أُوقفت هذه الفاتورة على مبلغ'] as $text) {
            $this->assertStringContainsString($text, $ar, "$template (ar): $text");
        }

        $this->assertStringContainsString('Invoice', AlgeriaInvoice::html($this->invoice(), 'en', $template));
    }

    public function test_templates_look_different(): void
    {
        $html = array_map(fn ($t) => AlgeriaInvoice::html($this->invoice(), 'fr', $t), ['classic', 'modern', 'minimal']);

        $this->assertCount(3, array_unique($html));
        $this->assertStringContainsString('#1e3a5f', $html[1]);
        $this->assertStringContainsString('Cachet et signature', $html[2]);
    }

    public function test_default_template_comes_from_config(): void
    {
        $this->assertSame(AlgeriaInvoice::html($this->invoice()), AlgeriaInvoice::html($this->invoice(), 'fr', 'classic'));

        config(['algeria-invoice.template' => 'minimal']);
        $this->app->forgetInstance(\Djouamaah\AlgeriaInvoice\AlgeriaInvoice::class);
        AlgeriaInvoice::clearResolvedInstances();

        $this->assertStringContainsString('Cachet et signature', AlgeriaInvoice::html($this->invoice()));
    }

    public function test_custom_template_from_config_or_view_name(): void
    {
        View::addNamespace('shop', __DIR__.'/fixtures');
        config(['algeria-invoice.templates.shop' => 'shop::invoice']);
        $this->app->forgetInstance(\Djouamaah\AlgeriaInvoice\AlgeriaInvoice::class);
        AlgeriaInvoice::clearResolvedInstances();

        $this->assertArrayHasKey('shop', AlgeriaInvoice::templates());
        $this->assertArrayHasKey('modern', AlgeriaInvoice::templates());
        $this->assertStringContainsString('SHOP FA-2026-00042 259 670,00', str_replace("\u{202F}", ' ', AlgeriaInvoice::html($this->invoice(), 'fr', 'shop')));
        $this->assertStringContainsString('SHOP FA-2026-00042', AlgeriaInvoice::html($this->invoice(), 'fr', 'shop::invoice'));
    }

    public function test_unknown_template(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown invoice template [fancy]. Available: classic, modern, minimal');

        AlgeriaInvoice::html($this->invoice(), 'fr', 'fancy');
    }

    #[DataProvider('templates')]
    public function test_pdf_for_each_template(string $template): void
    {
        if (! class_exists(\Mpdf\Mpdf::class)) {
            $this->markTestSkipped('mpdf/mpdf is not installed.');
        }

        $this->assertStringStartsWith('%PDF', AlgeriaInvoice::pdf($this->invoice(), 'ar', $template));
        $this->assertStringStartsWith('%PDF', AlgeriaInvoice::pdf($this->invoice(), 'fr', $template));
    }
}
