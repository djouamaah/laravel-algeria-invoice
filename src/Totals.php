<?php

namespace Djouamaah\AlgeriaInvoice;

/**
 * Invoice totals, all in centimes.
 */
class Totals
{
    /**
     * @param  array<string, array{base: int, vat: int}>  $vatBreakdown  keyed by rate ("19", "9", "0")
     */
    public function __construct(
        public readonly int $totalHt,
        public readonly array $vatBreakdown,
        public readonly int $totalVat,
        public readonly int $totalTtc,
        public readonly int $stampDuty,
        public readonly int $netToPay,
    ) {
    }

    public function toArray(): array
    {
        return [
            'total_ht' => $this->totalHt,
            'vat_breakdown' => $this->vatBreakdown,
            'total_vat' => $this->totalVat,
            'total_ttc' => $this->totalTtc,
            'stamp_duty' => $this->stampDuty,
            'net_to_pay' => $this->netToPay,
        ];
    }
}
