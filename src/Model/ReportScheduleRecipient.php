<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

use PinVandaag\BuckarooAPI\Model\Enum\ReportNotificationChannel;

final readonly class ReportScheduleRecipient
{
    public function __construct(
        public ReportNotificationChannel|string|null $notificationChannel = null,
        public ?string $notificationAddress = null,
        public ?string $recipientName = null,
    ) {
    }
}