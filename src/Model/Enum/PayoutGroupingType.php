<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum PayoutGroupingType: string
{
    case Merchant = 'Merchant';
    case Store = 'Store';
    case Terminal = 'Terminal';
}