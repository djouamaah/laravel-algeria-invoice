<?php

namespace Djouamaah\AlgeriaInvoice\Tests;

use Djouamaah\AlgeriaInvoice\Enums\DocumentType;
use Djouamaah\AlgeriaInvoice\Enums\PaymentMethod;
use Djouamaah\AlgeriaInvoice\Facades\AlgeriaInvoice;
use Djouamaah\AlgeriaInvoice\Invoice;
use Djouamaah\AlgeriaInvoice\Line;
use Djouamaah\AlgeriaInvoice\Money;

class InvoiceTest extends TestCase
{
    protected function sample(PaymentMethod $payment = PaymentMethod::Cash): Invoice
    {
        return AlgeriaInvoice::make()
            ->number('FA-2026-00001')
            ->date('2026-10-23')
            ->buyer(self::companyBuyer())
            ->addLine('Ordinateur portable', 2, 85000, 19)
            ->addLine('Livre scolaire', 3, 1200, 9)
            ->addLine(new Line('Formation', 1, 500, 0, discount: 10))
            ->payment($payment);
    }

    public function test_totals_per_vat_rate(): void
    {
        $totals = $this->sample()->totals();

        $this->assertSame(Money::toCentimes(174050), $totals->totalHt);
        $this->assertSame(['base' => Money::toCentimes(170000), 'vat' => Money::toCentimes(32300)], $totals->vatBreakdown['19']);
        $this->assertSame(['base' => Money::toCentimes(3600), 'vat' => Money::toCentimes(324)], $totals->vatBreakdown['9']);
        $this->assertSame(['base' => Money::toCentimes(450), 'vat' => 0], $totals->vatBreakdown['0']);
        $this->assertSame(Money::toCentimes(32624), $totals->totalVat);
        $this->assertSame(Money::toCentimes(206674), $totals->totalTtc);
        // 2067 started slices of 100 DA at 2 DA
        $this->assertSame(Money::toCentimes(4134), $totals->stampDuty);
        $this->assertSame(Money::toCentimes(210808), $totals->netToPay);
    }

    public function test_no_stamp_duty_without_cash(): void
    {
        $this->assertSame(0, $this->sample(PaymentMethod::Electronic)->totals()->stampDuty);
        $this->assertSame(0, $this->sample(PaymentMethod::Transfer)->totals()->stampDuty);
        $this->assertSame(0, $this->sample()->type(DocumentType::Proforma)->totals()->stampDuty);
    }

    public function test_seller_comes_from_config_and_sample_is_compliant(): void
    {
        $invoice = $this->sample();

        $this->assertSame('SARL Exemple Informatique', $invoice->getSeller()->name);
        $this->assertSame([], $invoice->errors());
        $this->assertTrue($invoice->isCompliant());
    }

    public function test_missing_mentions(): void
    {
        $invoice = Invoice::make()
            ->seller(['name' => 'SARL Sans Papiers', 'legal_form' => 'SARL', 'address' => 'Oran', 'nif' => '123'])
            ->buyer(['name' => 'EURL Client', 'legal_form' => 'EURL'])
            ->addLine('', 0, 100, 7);

        $errors = $invoice->errors();

        foreach (['number', 'seller.capital', 'seller.rc', 'seller.nis', 'seller.ai', 'seller.nif', 'buyer.address',
            'buyer.rc', 'buyer.nif', 'lines.0.description', 'lines.0.quantity', 'lines.0.vat_rate', 'payment_method'] as $field) {
            $this->assertArrayHasKey($field, $errors, $field);
        }
        app()->setLocale('fr');
        $this->assertSame('Le NIF du vendeur doit comporter 15 ou 20 chiffres.', $invoice->errors()['seller.nif']);
        app()->setLocale('ar');
        $this->assertSame('السجل التجاري للزبون إلزامي.', $invoice->errors()['buyer.rc']);
    }

    public function test_private_buyer_and_credit_note(): void
    {
        $invoice = $this->sample()->buyer(['name' => 'Karim B.', 'address' => 'Béjaïa']);
        $this->assertSame([], $invoice->errors());

        $credit = $this->sample()->type(DocumentType::CreditNote)->number('AV-2026-00001');
        $this->assertArrayHasKey('original_number', $credit->errors());
        $this->assertSame([], $credit->original('FA-2026-00001')->errors());
        $this->assertSame(0, $credit->totals()->stampDuty);
    }

    public function test_round_trip_to_array(): void
    {
        $invoice = $this->sample();
        $copy = Invoice::fromArray($invoice->toArray());

        $this->assertEquals($invoice->totals(), $copy->totals());
        $this->assertSame($invoice->toArray(), $copy->toArray());
    }

    public function test_money_helpers(): void
    {
        $this->assertSame(125050, Money::toCentimes('1 250,50'));
        $this->assertSame("1\u{202F}250,50 DA", Money::format(125050));
        $this->assertSame('1.250,50 دج', Money::format(125050, 'ar'));
        $this->assertSame(Money::toCentimes(4134), AlgeriaInvoice::stampDuty(Money::toCentimes(206674)));
        $this->assertSame('ألف دينار جزائري', AlgeriaInvoice::words(100000, 'ar'));
    }
}
