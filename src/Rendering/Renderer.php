<?php

namespace Djouamaah\AlgeriaInvoice\Rendering;

use Djouamaah\AlgeriaInvoice\Invoice;
use Djouamaah\AlgeriaInvoice\Words\AmountInWords;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Response;
use RuntimeException;

class Renderer
{
    public function __construct(
        protected ViewFactory $views,
        protected array $config = [],
    ) {
    }

    /**
     * Printable HTML in 'fr', 'ar' or 'en'.
     */
    public function html(Invoice $invoice, string $locale = 'fr', string $view = 'algeria-invoice::invoice'): string
    {
        $totals = $invoice->totals();
        $previous = app()->getLocale();
        app()->setLocale($locale);

        try {
            return $this->views->make($view, [
                'invoice' => $invoice,
                'totals' => $totals,
                'locale' => $locale,
                'rtl' => str_starts_with($locale, 'ar'),
                'sentence' => AmountInWords::sentence($totals->netToPay, $locale === 'ar' ? 'ar' : 'fr', $invoice->getType()),
            ])->render();
        } finally {
            app()->setLocale($previous);
        }
    }

    /**
     * PDF bytes. Requires mpdf/mpdf (composer require mpdf/mpdf), which shapes Arabic text correctly.
     */
    public function pdf(Invoice $invoice, string $locale = 'fr', string $view = 'algeria-invoice::invoice'): string
    {
        if (! class_exists(\Mpdf\Mpdf::class)) {
            throw new RuntimeException('PDF output needs mpdf/mpdf: composer require mpdf/mpdf');
        }

        $tempDir = $this->config['temp_dir'] ?? storage_path('app/algeria-invoice-tmp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => $this->config['format'] ?? 'A4',
            'tempDir' => $tempDir,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'margin_top' => 12,
            'margin_bottom' => 12,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);
        if (str_starts_with($locale, 'ar')) {
            $mpdf->SetDirectionality('rtl');
        }
        $mpdf->SetTitle((string) ($invoice->getNumber() ?? 'invoice'));
        $mpdf->WriteHTML($this->html($invoice, $locale, $view));

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    /**
     * Download response: return AlgeriaInvoice::download($invoice) from a controller.
     */
    public function download(Invoice $invoice, string $locale = 'fr', ?string $filename = null): Response
    {
        $filename ??= preg_replace('/[^A-Za-z0-9_\-]/', '_', $invoice->getNumber() ?? 'invoice').'.pdf';

        return new Response($this->pdf($invoice, $locale), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
