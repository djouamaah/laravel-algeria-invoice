<?php

namespace Djouamaah\AlgeriaInvoice;

use Djouamaah\AlgeriaInvoice\Enums\DocumentType;
use Djouamaah\AlgeriaInvoice\Numbering\Sequence;
use Djouamaah\AlgeriaInvoice\Rendering\Renderer;
use Djouamaah\AlgeriaInvoice\Tax\StampDuty;
use Djouamaah\AlgeriaInvoice\Words\AmountInWords;
use Illuminate\Http\Response;

/**
 * Entry point behind the AlgeriaInvoice facade.
 */
class AlgeriaInvoice
{
    public function __construct(
        protected array $config,
        protected Sequence $sequence,
        protected Renderer $renderer,
    ) {
    }

    /**
     * A new invoice with the seller from config('algeria-invoice.seller').
     */
    public function make(DocumentType|string $type = DocumentType::Invoice): Invoice
    {
        $invoice = Invoice::make()->type($type);

        if (! empty($this->config['seller'])) {
            $invoice->seller(Party::make($this->config['seller']));
        }

        return $invoice;
    }

    /**
     * Reserve the next gap-free number ("FA-2026-00001"). Call it inside the transaction that saves the invoice.
     */
    public function nextNumber(DocumentType|string $type = DocumentType::Invoice, ?int $year = null, ?string $series = null): string
    {
        return $this->sequence->next($type, $year, $series);
    }

    public function sequence(): Sequence
    {
        return $this->sequence;
    }

    /**
     * Cash stamp duty in centimes for an amount in centimes.
     */
    public function stampDuty(int $centimes): int
    {
        return (new StampDuty($this->config['stamp_duty'] ?? []))->calculate($centimes);
    }

    /**
     * Amount in words: words(125050) or words(125050, 'ar').
     */
    public function words(int $centimes, string $locale = 'fr'): string
    {
        return AmountInWords::words($centimes, $locale);
    }

    public function html(Invoice $invoice, string $locale = 'fr'): string
    {
        return $this->renderer->html($invoice, $locale);
    }

    public function pdf(Invoice $invoice, string $locale = 'fr'): string
    {
        return $this->renderer->pdf($invoice, $locale);
    }

    public function download(Invoice $invoice, string $locale = 'fr', ?string $filename = null): Response
    {
        return $this->renderer->download($invoice, $locale, $filename);
    }
}
