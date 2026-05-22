<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum PayoutInterval: string
{
    case Weekly = 'Weekly';
    case Monthly = 'Monthly';
}