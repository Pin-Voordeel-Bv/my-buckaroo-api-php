<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

use PinVandaag\BuckarooAPI\Model\Enum\ReportScope;
use PinVandaag\BuckarooAPI\Model\Enum\ReportStatus;
use PinVandaag\BuckarooAPI\Model\Enum\ReportType;

final readonly class Report
{
    /**
     * @param array<int, ReportRecipient|array<string, mixed>> $recipients
     * @param array<string, mixed> $links
     */
    public function __construct(
        public string $id = '',
        public string $reportDefinitionId = '',
        public ReportScope|string|null $scope = null,
        public ReportType|string|null $type = null,
        public ?string $storeId = null,
        public ?string $user = null,
        public ReportStatus|string|null $status = null,
        public ?string $fileType = null,
        public ?string $fileName = null,
        public array $recipients = [],
        public string $createdAt = '',
        public array $links = [],
    ) {
    }
}