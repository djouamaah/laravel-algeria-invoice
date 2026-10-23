<?php

namespace Djouamaah\AlgeriaInvoice\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Djouamaah\AlgeriaInvoice\Invoice make(\Djouamaah\AlgeriaInvoice\Enums\DocumentType|string $type = 'invoice')
 * @method static string nextNumber(\Djouamaah\AlgeriaInvoice\Enums\DocumentType|string $type = 'invoice', ?int $year = null, ?string $series = null)
 * @method static \Djouamaah\AlgeriaInvoice\Numbering\Sequence sequence()
 * @method static int stampDuty(int $centimes)
 * @method static string words(int $centimes, string $locale = 'fr')
 * @method static string html(\Djouamaah\AlgeriaInvoice\Invoice $invoice, string $locale = 'fr')
 * @method static string pdf(\Djouamaah\AlgeriaInvoice\Invoice $invoice, string $locale = 'fr')
 * @method static \Illuminate\Http\Response download(\Djouamaah\AlgeriaInvoice\Invoice $invoice, string $locale = 'fr', ?string $filename = null)
 *
 * @see \Djouamaah\AlgeriaInvoice\AlgeriaInvoice
 */
class AlgeriaInvoice extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Djouamaah\AlgeriaInvoice\AlgeriaInvoice::class;
    }
}
