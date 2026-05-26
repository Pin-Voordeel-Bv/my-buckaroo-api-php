<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum ReportDefinitionStatus: string
{
    case Active = 'Active';
    case Inactive = 'Inactive';
    case Archived = 'Archived';
}