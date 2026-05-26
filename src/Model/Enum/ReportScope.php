<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum ReportScope: string
{
    case MerchantGroup = 'MerchantGroup';
    case Merchant = 'Merchant';
    case Store = 'Store';
    case Terminal = 'Terminal';
}