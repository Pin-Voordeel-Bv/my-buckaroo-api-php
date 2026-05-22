<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum PaymentMethodSubscriptionAction: string
{
    case Update = 'Update';
    case Activate = 'Activate';
    case Deactivate = 'Deactivate';
}