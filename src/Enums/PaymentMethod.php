<?php

namespace Djouamaah\AlgeriaInvoice\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Cheque = 'cheque';
    case Transfer = 'transfer';
    case Electronic = 'electronic';
    case Other = 'other';

    /**
     * Only cash payments pay the stamp duty of art. 100-I of the Code du timbre.
     */
    public function hasStampDuty(): bool
    {
        return $this === self::Cash;
    }
}
