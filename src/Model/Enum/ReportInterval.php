<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum ReportInterval: string
{
    case Daily = 'Daily';
    case Weekly = 'Weekly';
    case Monthly = 'Monthly';
    case Event = 'Event';
}