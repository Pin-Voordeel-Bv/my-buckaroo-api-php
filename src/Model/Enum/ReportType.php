<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum ReportType: string
{
    case Manual = 'Manual';
    case Scheduled = 'Scheduled';
    case EventBased = 'EventBased';
}