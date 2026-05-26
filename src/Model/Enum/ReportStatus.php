<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum ReportStatus: string
{
    case Queued = 'Queued';
    case Processing = 'Processing';
    case Failed = 'Failed';
    case Done = 'Done';
}