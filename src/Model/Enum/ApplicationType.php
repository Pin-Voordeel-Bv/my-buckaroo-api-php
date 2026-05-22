<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum ApplicationType: string
{
    case System = 'System';
    case Merchant = 'Merchant';
    case ThirdParty = 'ThirdParty';
    case Native = 'Native';
}