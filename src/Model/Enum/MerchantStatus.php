<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum MerchantStatus: string
{
    case Test = 'Test';
    case Active = 'Active';
    case OnHold = 'OnHold';
    case Suspended = 'Suspended';
    case NonActive = 'NonActive';
    case Archived = 'Archived';
}