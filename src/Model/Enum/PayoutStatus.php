<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum PayoutStatus: string
{
    case Pending = 'Pending';
    case Prepared = 'Prepared';
    case Released = 'Released';
    case Sent = 'Sent';
    case Processed = 'Processed';
    case Failed = 'Failed';
    case Bounced = 'Bounced';
}