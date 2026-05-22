<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum PayoutStatementIbanVariant: string
{
    case PayoutIban = 'PayoutIban';
    case VirtualIban = 'VirtualIban';
}