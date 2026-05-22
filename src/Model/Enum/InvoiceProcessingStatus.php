<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum InvoiceProcessingStatus: string
{
    case Created = 'Created';
    case Finalized = 'Finalized';
}