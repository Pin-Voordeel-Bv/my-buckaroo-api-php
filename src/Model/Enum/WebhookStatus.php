<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum WebhookStatus: string
{
    case Active = 'Active';
    case Inactive = 'Inactive';
}