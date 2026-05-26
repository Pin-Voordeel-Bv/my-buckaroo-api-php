<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum ReportDeliveryStatus: string
{
    case Pending = 'Pending';
    case Success = 'Success';
    case Failed = 'Failed';
}