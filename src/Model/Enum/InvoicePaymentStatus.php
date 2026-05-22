<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum InvoicePaymentStatus: string
{
    case Open = 'Open';
    case PartiallyPaid = 'PartiallyPaid';
    case Paid = 'Paid';
    case Overpaid = 'Overpaid';
}