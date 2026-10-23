<?php

namespace Djouamaah\AlgeriaInvoice;

use InvalidArgumentException;

/**
 * An invoice line. Prices are excluding VAT (HT).
 */
class Line
{
    /** Unit price excluding VAT, in centimes. */
    public readonly int $unitPrice;

    public function __construct(
        public readonly string $description,
        public readonly float $quantity,
        int|float|string $unitPrice,
        public readonly int|float $vatRate = 19,
        public readonly ?string $unit = null,
        public readonly float $discount = 0.0,
        public readonly ?string $reference = null,
    ) {
        if ($discount < 0 || $discount > 100) {
            throw new InvalidArgumentException('The discount is a percentage between 0 and 100.');
        }
        if ($vatRate < 0) {
            throw new InvalidArgumentException('The VAT rate cannot be negative.');
        }

        $this->unitPrice = Money::toCentimes($unitPrice);
    }

    public static function make(array $data): static
    {
        return new static(
            description: (string) ($data['description'] ?? ''),
            quantity: (float) ($data['quantity'] ?? 1),
            unitPrice: $data['unit_price'] ?? $data['unitPrice'] ?? 0,
            vatRate: $data['vat_rate'] ?? $data['vatRate'] ?? 19,
            unit: $data['unit'] ?? null,
            discount: (float) ($data['discount'] ?? 0),
            reference: $data['reference'] ?? null,
        );
    }

    /**
     * Line total excluding VAT, after discount, in centimes.
     */
    public function total(): int
    {
        return (int) round($this->quantity * $this->unitPrice * (1 - $this->discount / 100));
    }

    public function toArray(): array
    {
        return array_filter([
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price' => Money::toDinars($this->unitPrice),
            'vat_rate' => $this->vatRate,
            'unit' => $this->unit,
            'discount' => $this->discount ?: null,
            'reference' => $this->reference,
        ], fn ($v) => $v !== null);
    }
}
