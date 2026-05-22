<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum InvoiceAttachmentType: string
{
    case Pdf = 'Pdf';
    case Html = 'Html';
    case Csv = 'Csv';
}