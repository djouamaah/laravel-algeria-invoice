<?php

namespace Djouamaah\AlgeriaInvoice;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * A document the invoice refers to: purchase order (bon de commande) or delivery note (bon de livraison).
 */
class Reference
{
    public readonly ?DateTimeInterface $date;

    public function __construct(
        public readonly string $number,
        DateTimeInterface|string|null $date = null,
    ) {
        if (trim($number) === '') {
            throw new InvalidArgumentException('A reference needs a number.');
        }

        $this->date = is_string($date) ? new DateTimeImmutable($date) : $date;
    }

    /**
     * Reference::make('BL-12'), Reference::make(['number' => 'BL-12', 'date' => '2026-10-20']) or a Reference.
     */
    public static function make(self|array|string $reference, DateTimeInterface|string|null $date = null): static
    {
        return match (true) {
            $reference instanceof self => $reference,
            is_array($reference) => new static((string) ($reference['number'] ?? ''), $reference['date'] ?? null),
            default => new static($reference, $date),
        };
    }

    public function toArray(): array
    {
        return ['number' => $this->number, 'date' => $this->date?->format('Y-m-d')];
    }
}
