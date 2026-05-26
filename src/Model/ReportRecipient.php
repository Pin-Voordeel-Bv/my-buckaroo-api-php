<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

use PinVandaag\BuckarooAPI\Model\Enum\ReportDeliveryStatus;
use PinVandaag\BuckarooAPI\Model\Enum\ReportNotificationChannel;

final readonly class ReportRecipient
{
    public function __construct(
        public ReportDeliveryStatus|string|null $deliveryStatus = null,
        public ReportNotificationChannel|string|null $notificationChannel = null,
        public ?string $notificationAddress = null,
        public ?string $recipientName = null,
    ) {
    }
}