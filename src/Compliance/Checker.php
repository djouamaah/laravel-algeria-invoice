<?php

namespace Djouamaah\AlgeriaInvoice\Compliance;

use Djouamaah\AlgeriaInvoice\Enums\DocumentType;
use Djouamaah\AlgeriaInvoice\Invoice;
use Djouamaah\AlgeriaInvoice\Party;

/**
 * Checks the mandatory mentions of an invoice (executive decree 05-468 of 10 December 2005).
 *
 * Seller: name, address, legal form (+ share capital for companies), RC, NIF, NIS, AI.
 * Buyer: name and address; RC and NIF too when the buyer is a professional.
 * Document: number, date, lines, payment method; a credit note names the invoice it corrects.
 */
class Checker
{
    /**
     * @return array<string, string> field => message, empty when compliant
     */
    public function check(Invoice $invoice): array
    {
        $errors = [];
        $type = $invoice->getType();
        $binding = $type->isBinding();

        if ($binding && blank($invoice->getNumber())) {
            $errors['number'] = $this->message('required', 'number');
        }

        $seller = $invoice->getSeller();
        if (! $seller || blank($seller->name)) {
            $errors['seller.name'] = $this->message('required', 'seller.name');
        }
        if ($seller) {
            $required = ['address', 'legal_form', 'rc', 'nif', 'nis', 'ai'];
            if ($this->isCompany($seller)) {
                $required[] = 'capital';
            }
            $errors += $this->requireFields($seller, 'seller', $binding ? $required : ['address']);
            $errors += $this->formats($seller, 'seller');
        }

        $buyer = $invoice->getBuyer();
        if (! $buyer || blank($buyer->name)) {
            $errors['buyer.name'] = $this->message('required', 'buyer.name');
        }
        if ($buyer) {
            $required = ['address'];
            if ($binding && $buyer->isProfessional()) {
                array_push($required, 'rc', 'nif');
            }
            $errors += $this->requireFields($buyer, 'buyer', $required);
            $errors += $this->formats($buyer, 'buyer');
        }

        if ($invoice->getLines() === []) {
            $errors['lines'] = $this->message('no_lines', 'lines');
        }
        $rates = function_exists('app') && app()->bound('config') ? (array) config('algeria-invoice.vat_rates', [19, 9, 0]) : [19, 9, 0];
        foreach ($invoice->getLines() as $i => $line) {
            if (blank($line->description)) {
                $errors["lines.$i.description"] = $this->message('required', 'description', $i + 1);
            }
            if ($line->quantity <= 0) {
                $errors["lines.$i.quantity"] = $this->message('quantity', 'quantity', $i + 1);
            }
            if ($line->unitPrice < 0) {
                $errors["lines.$i.unit_price"] = $this->message('unit_price', 'unit_price', $i + 1);
            }
            if (! in_array($line->vatRate, $rates, false)) {
                $errors["lines.$i.vat_rate"] = $this->message('vat_rate', 'vat_rate', $i + 1);
            }
        }

        if ($type === DocumentType::Invoice && $invoice->getPaymentMethod() === null) {
            $errors['payment_method'] = $this->message('required', 'payment_method');
        }
        if ($type === DocumentType::CreditNote && blank($invoice->getOriginalNumber())) {
            $errors['original_number'] = $this->message('required', 'original_number');
        }

        return $errors;
    }

    protected function isCompany(Party $party): bool
    {
        $forms = function_exists('app') && app()->bound('config')
            ? (array) config('algeria-invoice.company_forms', [])
            : ['SARL', 'EURL', 'SPA', 'SNC', 'SCS', 'SCA', 'EPE', 'EPIC', 'SPAS', 'SPASU'];

        return $party->legalForm !== null
            && in_array(strtoupper(str_replace(['.', ' '], '', $party->legalForm)), $forms, true);
    }

    /**
     * @return array<string, string>
     */
    protected function requireFields(Party $party, string $prefix, array $fields): array
    {
        $errors = [];
        foreach ($fields as $field) {
            $value = match ($field) {
                'address' => $party->address,
                'legal_form' => $party->legalForm,
                'capital' => $party->capital,
                default => $party->{$field},
            };
            if (blank($value)) {
                $errors["$prefix.$field"] = $this->message('required', "$prefix.$field");
            }
        }

        return $errors;
    }

    /**
     * NIF: 15 digits (20 for the new NIF), NIS: 15 digits, RIB: 20 digits. Spaces are ignored.
     *
     * @return array<string, string>
     */
    protected function formats(Party $party, string $prefix): array
    {
        $errors = [];
        $digits = fn (?string $v) => $v === null ? null : preg_replace('/[\s.\-]/', '', $v);

        if (filled($party->nif) && ! preg_match('/^(\d{15}|\d{20})$/', $digits($party->nif))) {
            $errors["$prefix.nif"] = $this->message('nif', "$prefix.nif");
        }
        if (filled($party->nis) && ! preg_match('/^\d{15}$/', $digits($party->nis))) {
            $errors["$prefix.nis"] = $this->message('nis', "$prefix.nis");
        }
        if (filled($party->rib) && ! preg_match('/^\d{20}$/', $digits($party->rib))) {
            $errors["$prefix.rib"] = $this->message('rib', "$prefix.rib");
        }

        return $errors;
    }

    protected function message(string $rule, string $field, ?int $line = null): string
    {
        $replace = ['field' => $this->label($field), 'line' => $line];

        if (function_exists('app') && app()->bound('translator')) {
            return trans('algeria-invoice::invoice.errors.'.$rule, $replace);
        }

        $fallback = [
            'required' => ':field is required.',
            'no_lines' => 'The invoice has no line.',
            'quantity' => 'Line :line: the quantity must be greater than zero.',
            'unit_price' => 'Line :line: the unit price cannot be negative.',
            'vat_rate' => 'Line :line: unknown VAT rate.',
            'nif' => ':field must have 15 or 20 digits.',
            'nis' => ':field must have 15 digits.',
            'rib' => ':field must have 20 digits.',
        ][$rule];

        return strtr($fallback, [':field' => $replace['field'], ':line' => (string) $line]);
    }

    protected function label(string $field): string
    {
        if (function_exists('app') && app()->bound('translator')) {
            $key = 'algeria-invoice::invoice.fields.'.$field;
            $label = trans($key);

            return $label === $key ? $field : $label;
        }

        return $field;
    }
}
