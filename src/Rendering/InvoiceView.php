<?php

namespace Djouamaah\AlgeriaInvoice\Rendering;

use Djouamaah\AlgeriaInvoice\Invoice;
use Djouamaah\AlgeriaInvoice\Line;
use Djouamaah\AlgeriaInvoice\Money;
use Djouamaah\AlgeriaInvoice\Party;
use Djouamaah\AlgeriaInvoice\Reference;
use Djouamaah\AlgeriaInvoice\Totals;
use Djouamaah\AlgeriaInvoice\Words\AmountInWords;

/**
 * Everything a template needs, already translated and formatted: templates only lay it out.
 * Available in every template as $v.
 */
class InvoiceView
{
    public readonly Totals $totals;

    public readonly bool $rtl;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly string $locale,
    ) {
        $this->totals = $invoice->totals();
        $this->rtl = str_starts_with($locale, 'ar');
    }

    public function t(string $key, array $replace = []): string
    {
        return __('algeria-invoice::invoice.'.$key, $replace);
    }

    public function title(): string
    {
        return $this->t('titles.'.$this->invoice->getType()->value);
    }

    /** CSS text-align for the start / end of a line ('left' / 'right', swapped in Arabic). */
    public function start(): string
    {
        return $this->rtl ? 'right' : 'left';
    }

    public function end(): string
    {
        return $this->rtl ? 'left' : 'right';
    }

    public function money(int $centimes, bool $currency = true): string
    {
        return Money::format($centimes, $this->locale, $currency);
    }

    public function date(?\DateTimeInterface $date): ?string
    {
        return $date?->format('d/m/Y');
    }

    public function quantity(Line $line): string
    {
        return rtrim(rtrim(number_format($line->quantity, 3, ',', ' '), '0'), ',');
    }

    public function discount(Line $line): string
    {
        return $line->discount ? rtrim(rtrim(number_format($line->discount, 2, ',', ''), '0'), ',').' %' : '';
    }

    public function hasDiscount(): bool
    {
        foreach ($this->invoice->getLines() as $line) {
            if ($line->discount > 0) {
                return true;
            }
        }

        return false;
    }

    public function hasUnit(): bool
    {
        foreach ($this->invoice->getLines() as $line) {
            if (filled($line->unit)) {
                return true;
            }
        }

        return false;
    }

    public function payment(): ?string
    {
        $method = $this->invoice->getPaymentMethod();

        return $method ? $this->t('payment.'.$method->value) : null;
    }

    /**
     * Document references shown next to the title: number, date, due date, original invoice, purchase order,
     * delivery notes. 'ltr' is false when the value mixes in translated words ("du", "بتاريخ").
     *
     * @return array<int, array{label: string, value: string, ltr: bool}>
     */
    public function references(): array
    {
        $i = $this->invoice;

        $references = array_filter([
            $i->getNumber() ? ['label' => $this->t('labels.number'), 'value' => $i->getNumber(), 'ltr' => true] : null,
            ['label' => $this->t('labels.date'), 'value' => $this->date($i->getDate()), 'ltr' => true],
            $i->getDueDate() ? ['label' => $this->t('labels.due_date'), 'value' => $this->date($i->getDueDate()), 'ltr' => true] : null,
            $i->getOriginalNumber() ? ['label' => $this->t('labels.original'), 'value' => $i->getOriginalNumber(), 'ltr' => true] : null,
            $i->getOrder() ? ['label' => $this->t('labels.order'), 'value' => $this->reference($i->getOrder()), 'ltr' => $i->getOrder()->date === null] : null,
        ]);

        // one line per delivery note: mixing several Latin numbers and Arabic words on one line breaks the reading order
        foreach ($i->getDeliveryNotes() as $note) {
            $references[] = ['label' => $this->t('labels.delivery_note'), 'value' => $this->reference($note), 'ltr' => $note->date === null];
        }

        return array_values($references);
    }

    /** "BL-2026-0451 du 21/10/2026" / "BL-2026-0451 بتاريخ 21/10/2026" */
    public function reference(Reference $reference): string
    {
        return $reference->number.($reference->date ? ' '.$this->t('labels.dated').' '.$this->date($reference->date) : '');
    }

    /**
     * Lines describing a party, without its name: legal form and capital, address, contact.
     *
     * @return string[]
     */
    public function partyLines(?Party $party): array
    {
        if (! $party) {
            return [];
        }

        $lines = [];
        if ($party->legalForm) {
            $lines[] = $party->legalForm.($party->capital !== null
                ? ' - '.$this->t('labels.capital').' : '.$this->money(Money::toCentimes($party->capital))
                : '');
        }
        if ($party->address) {
            $lines[] = $party->address;
        }
        $contact = array_filter([$party->phone ? $this->t('labels.phone').' : '.$party->phone : null, $party->email]);
        if ($contact) {
            $lines[] = implode(' - ', $contact);
        }

        return $lines;
    }

    /**
     * Legal identifiers of a party: RC, NIF, NIS, AI, RIB (only those given).
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function partyIds(?Party $party): array
    {
        if (! $party) {
            return [];
        }

        $ids = [];
        foreach (['rc', 'nif', 'nis', 'ai', 'rib'] as $key) {
            if (filled($party->{$key})) {
                $ids[] = ['label' => $this->t('labels.'.$key), 'value' => $party->{$key}];
            }
        }

        return $ids;
    }

    /**
     * Rows between the total excluding VAT and the total including VAT.
     *
     * @return array<int, array{label: string, base: string, amount: string, exempt: bool}>
     */
    public function vatRows(): array
    {
        $rows = [];
        foreach ($this->totals->vatBreakdown as $rate => $row) {
            $exempt = (float) $rate == 0.0;
            $rows[] = [
                'label' => $exempt ? $this->t('labels.vat_exempt') : $this->t('labels.vat', ['rate' => $rate]),
                'base' => $this->money($row['base']),
                'amount' => $exempt ? '' : $this->money($row['vat']),
                'exempt' => $exempt,
            ];
        }

        return $rows;
    }

    /** "Arrêtée la présente facture à la somme de : ..." in French or Arabic. */
    public function sentence(): string
    {
        return AmountInWords::sentence($this->totals->netToPay, $this->rtl ? 'ar' : 'fr', $this->invoice->getType());
    }
}
