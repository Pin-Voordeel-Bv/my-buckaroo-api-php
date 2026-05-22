<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum PayoutConsolidation: string
{
    case None = 'None';
    case Source = 'Source';
    case Store = 'Store';
}