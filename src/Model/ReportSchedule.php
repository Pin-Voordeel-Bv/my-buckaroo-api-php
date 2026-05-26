<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

use PinVandaag\BuckarooAPI\Model\Enum\ReportInterval;
use PinVandaag\BuckarooAPI\Model\Enum\ReportScheduleStatus;
use PinVandaag\BuckarooAPI\Model\Enum\ReportScope;

final readonly class ReportSchedule
{
    /**
     * @param array<int, ReportScheduleFilter|array<string, mixed>> $filters
     * @param array<int, ReportScheduleRecipient|array<string, mixed>> $recipients
     * @param array<string, mixed> $links
     */
    public function __construct(
        public string $id = '',
        public string $reportDefinitionId = '',
        public ?string $storeId = null,
        public ReportScheduleStatus|string|null $status = null,
        public ReportInterval|string|null $interval = null,
        public ReportScope|string|null $scope = null,
        public array $filters = [],
        public array $recipients = [],
        public ?string $nextTriggeredAt = null,
        public string $createdAt = '',
        public array $links = [],
    ) {
    }
}