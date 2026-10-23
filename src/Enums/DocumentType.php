<?php

namespace Djouamaah\AlgeriaInvoice\Enums;

enum DocumentType: string
{
    case Invoice = 'invoice';
    case CreditNote = 'credit_note';
    case Proforma = 'proforma';

    /**
     * A proforma is a quote: no payment, no number sequence requirement, no stamp duty.
     */
    public function isBinding(): bool
    {
        return $this !== self::Proforma;
    }
}
