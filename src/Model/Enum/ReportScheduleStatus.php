<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum ReportScheduleStatus: string
{
    case Active = 'Active';
    case Inactive = 'Inactive';
    case Archived = 'Archived';
}