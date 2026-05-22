<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum AccountStatus: string
{
    case Active = 'Active';
    case Inactive = 'Inactive';
}