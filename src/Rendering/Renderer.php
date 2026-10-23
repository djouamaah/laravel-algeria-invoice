<?php

namespace Djouamaah\AlgeriaInvoice\Rendering;

use Djouamaah\AlgeriaInvoice\Invoice;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Response;
use InvalidArgumentException;
use RuntimeException;

class Renderer
{
    public const TEMPLATES = [
        'classic' => 'algeria-invoice::templates.classic',
        'modern' => 'algeria-invoice::templates.modern',
        'minimal' => 'algeria-invoice::templates.minimal',
    ];

    /**
     * @param  array  $config  config('algeria-invoice'): uses 'template', 'templates' and 'pdf'
     */
    public function __construct(
        protected ViewFactory $views,
        protected array $config = [],
    ) {
    }

    /**
     * Template names and their views: built-in ones plus config('algeria-invoice.templates').
     *
     * @return array<string, string>
     */
    public function templates(): array
    {
        return array_merge(self::TEMPLATES, (array) ($this->config['templates'] ?? []));
    }

    /**
     * Printable HTML in 'fr', 'ar' or 'en', with a template name ('classic', 'modern', 'minimal',
     * one from config) or a view name. Null uses config('algeria-invoice.template').
     */
    public function html(Invoice $invoice, string $locale = 'fr', ?string $template = null): string
    {
        $view = $this->resolve($template);
        $previous = app()->getLocale();
        app()->setLocale($locale);

        try {
            $v = new InvoiceView($invoice, $locale);

            return $this->views->make($view, [
                'v' => $v,
                'invoice' => $invoice,
                'totals' => $v->totals,
                'locale' => $locale,
                'rtl' => $v->rtl,
            ])->render();
        } finally {
            app()->setLocale($previous);
        }
    }

    /**
     * PDF bytes. Requires mpdf/mpdf (composer require mpdf/mpdf), which shapes Arabic text correctly.
     */
    public function pdf(Invoice $invoice, string $locale = 'fr', ?string $template = null): string
    {
        if (! class_exists(\Mpdf\Mpdf::class)) {
            throw new RuntimeException('PDF output needs mpdf/mpdf: composer require mpdf/mpdf');
        }

        $html = $this->html($invoice, $locale, $template);
        $pdf = (array) ($this->config['pdf'] ?? []);

        $tempDir = $pdf['temp_dir'] ?? storage_path('app/algeria-invoice-tmp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => $pdf['format'] ?? 'A4',
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
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    /**
     * Download response: return AlgeriaInvoice::download($invoice) from a controller.
     */
    public function download(Invoice $invoice, string $locale = 'fr', ?string $filename = null, ?string $template = null): Response
    {
        $filename ??= preg_replace('/[^A-Za-z0-9_\-]/', '_', $invoice->getNumber() ?? 'invoice').'.pdf';

        return new Response($this->pdf($invoice, $locale, $template), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    protected function resolve(?string $template): string
    {
        $template ??= $this->config['template'] ?? 'classic';
        $templates = $this->templates();

        if (isset($templates[$template])) {
            return $templates[$template];
        }
        if (str_contains($template, '.') || str_contains($template, '::')) {
            if ($this->views->exists($template)) {
                return $template;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Unknown invoice template [%s]. Available: %s, or a view name.', $template, implode(', ', array_keys($templates)),
        ));
    }
}
