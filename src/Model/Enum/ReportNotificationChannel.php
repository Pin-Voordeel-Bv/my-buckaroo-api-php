<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum ReportNotificationChannel: string
{
    case Mail = 'Mail';
    case BuckarooSftp = 'BuckarooSftp';
}