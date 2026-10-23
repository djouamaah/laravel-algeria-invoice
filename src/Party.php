<?php

namespace Djouamaah\AlgeriaInvoice;

/**
 * Seller or buyer of an invoice.
 *
 * rc  = registre de commerce (CNRC)          nif = numéro d'identification fiscale (DGI)
 * nis = numéro d'identification statistique   ai  = numéro d'article d'imposition
 */
class Party
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $address = null,
        public readonly ?string $legalForm = null,
        public readonly int|float|null $capital = null,
        public readonly ?string $rc = null,
        public readonly ?string $nif = null,
        public readonly ?string $nis = null,
        public readonly ?string $ai = null,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?string $rib = null,
        public readonly ?bool $professional = null,
    ) {
    }

    /**
     * Party::make(['name' => ..., 'legal_form' => ..., 'nif' => ...])
     */
    public static function make(array $data): static
    {
        $get = fn (string $key) => $data[$key] ?? $data[lcfirst(str_replace('_', '', ucwords($key, '_')))] ?? null;

        return new static(
            name: (string) ($get('name') ?? ''),
            address: $get('address'),
            legalForm: $get('legal_form'),
            capital: $get('capital'),
            rc: $get('rc'),
            nif: $get('nif'),
            nis: $get('nis'),
            ai: $get('ai'),
            phone: $get('phone'),
            email: $get('email'),
            rib: $get('rib'),
            professional: $get('professional'),
        );
    }

    /**
     * A professional (company, trader) must show its identifiers; a private person need not.
     * Explicit 'professional' wins, otherwise any identifier marks the party as professional.
     */
    public function isProfessional(): bool
    {
        return $this->professional ?? ($this->rc !== null || $this->nif !== null || $this->legalForm !== null);
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'address' => $this->address,
            'legal_form' => $this->legalForm,
            'capital' => $this->capital,
            'rc' => $this->rc,
            'nif' => $this->nif,
            'nis' => $this->nis,
            'ai' => $this->ai,
            'phone' => $this->phone,
            'email' => $this->email,
            'rib' => $this->rib,
            'professional' => $this->professional,
        ], fn ($v) => $v !== null);
    }
}
