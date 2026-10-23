<?php

namespace Djouamaah\AlgeriaInvoice\Tests;

use Djouamaah\AlgeriaInvoice\Facades\AlgeriaInvoice;
use Djouamaah\AlgeriaInvoice\Invoice;
use Djouamaah\AlgeriaInvoice\Reference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

class ReferencesTest extends TestCase
{
    use RefreshDatabase;

    protected function invoice(): Invoice
    {
        return AlgeriaInvoice::make()
            ->number('FA-2026-00042')
            ->date('2026-10-23')
            ->buyer(self::companyBuyer())
            ->addLine('Ordinateur portable', 2, 85000, 19)
            ->payment('transfer')
            ->order('BC-2026-118', '2026-10-20')
            ->deliveryNote('BL-2026-0451', '2026-10-21')
            ->deliveryNote('BL-2026-0458', '2026-10-22');
    }

    public function test_order_and_delivery_notes(): void
    {
        $invoice = $this->invoice();

        $this->assertSame('BC-2026-118', $invoice->getOrder()->number);
        $this->assertSame('2026-10-20', $invoice->getOrder()->date->format('Y-m-d'));
        $this->assertSame('BC-2026-118', $invoice->getOrderReference());
        $this->assertSame(['BL-2026-0451', 'BL-2026-0458'], array_map(fn ($r) => $r->number, $invoice->getDeliveryNotes()));

        // the same delivery note is not added twice, its date is updated
        $invoice->deliveryNote('BL-2026-0451', '2026-10-25');
        $this->assertCount(2, $invoice->getDeliveryNotes());
        $this->assertSame('2026-10-25', $invoice->getDeliveryNotes()[1]->date->format('Y-m-d'));

        $invoice->deliveryNotes(['BL-1', ['number' => 'BL-2', 'date' => '2026-10-21'], new Reference('BL-3')]);
        $this->assertSame(['BL-1', 'BL-2', 'BL-3'], array_map(fn ($r) => $r->number, $invoice->getDeliveryNotes()));

        $invoice->order(null);
        $this->assertNull($invoice->getOrder());
        $this->assertSame([], $invoice->errors(), 'references are optional and do not affect compliance');
    }

    public function test_order_reference_still_works_as_in_1_0(): void
    {
        $invoice = Invoice::make()->orderReference('BC-7');

        $this->assertSame('BC-7', $invoice->getOrderReference());
        $this->assertNull($invoice->getOrder()->date);
        $this->assertNull(Invoice::make()->orderReference(null)->getOrder());
    }

    public function test_round_trip_and_data_saved_by_1_0(): void
    {
        $data = $this->invoice()->toArray();

        $this->assertSame('BC-2026-118', $data['order_reference']);
        $this->assertSame('2026-10-20', $data['order_date']);
        $this->assertSame([
            ['number' => 'BL-2026-0451', 'date' => '2026-10-21'],
            ['number' => 'BL-2026-0458', 'date' => '2026-10-22'],
        ], $data['delivery_notes']);
        $this->assertSame($data, Invoice::fromArray($data)->toArray());

        // arrays saved by 1.0 have only order_reference
        unset($data['order_date'], $data['delivery_notes']);
        $old = Invoice::fromArray($data);
        $this->assertSame('BC-2026-118', $old->getOrderReference());
        $this->assertSame([], $old->getDeliveryNotes());
    }

    public function test_reference_needs_a_number(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Reference('  ');
    }

    public static function templates(): array
    {
        return [['classic'], ['modern'], ['minimal']];
    }

    #[DataProvider('templates')]
    public function test_templates_show_the_references(string $template): void
    {
        $fr = AlgeriaInvoice::html($this->invoice(), 'fr', $template);
        $this->assertStringContainsString('Bon de commande', $fr);
        $this->assertStringContainsString('BC-2026-118 du 20/10/2026', $fr);
        $this->assertStringContainsString('BL-2026-0451 du 21/10/2026', $fr);
        $this->assertStringContainsString('BL-2026-0458 du 22/10/2026', $fr);
        $this->assertSame(2, substr_count($fr, 'Bon de livraison'), 'one line per delivery note');

        $ar = AlgeriaInvoice::html($this->invoice(), 'ar', $template);
        $this->assertStringContainsString('سند الطلب', $ar);
        $this->assertStringContainsString('BC-2026-118 بتاريخ 20/10/2026', $ar);
        $this->assertSame(2, substr_count($ar, 'وصل التسليم'));
        $this->assertStringContainsString('BL-2026-0451 بتاريخ 21/10/2026', $ar);
        $this->assertStringContainsString('BL-2026-0458 بتاريخ 22/10/2026', $ar);
        // mixed Arabic and Latin text is not forced left-to-right
        $this->assertStringNotContainsString('dir="ltr">BL-2026-0451 بتاريخ', $ar);

        // a delivery note without date is a bare number, kept left-to-right in Arabic
        $single = AlgeriaInvoice::html($this->invoice()->deliveryNotes(['BL-9']), 'ar', $template);
        $this->assertSame(1, substr_count($single, 'وصل التسليم'));
        $this->assertMatchesRegularExpression('/dir="ltr"\s*>BL-9</', $single);
    }

    public function test_delivery_notes_can_have_their_own_number_series(): void
    {
        $this->assertSame('BL-2026-00001', AlgeriaInvoice::nextNumber(year: 2026, series: 'BL'));
        $this->assertSame('BL-2026-00002', AlgeriaInvoice::nextNumber(year: 2026, series: 'BL'));
        $this->assertSame('FA-2026-00001', AlgeriaInvoice::nextNumber(year: 2026));
    }
}
