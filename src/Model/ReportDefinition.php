<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

use PinVandaag\BuckarooAPI\Model\Enum\ReportDefinitionStatus;
use PinVandaag\BuckarooAPI\Model\Enum\ReportType;

final readonly class ReportDefinition
{
    /**
     * @param array<int, LocalizedValue|array<string, mixed>> $descriptionResources
     * @param array<int, string> $allowedIntervals
     * @param array<int, ReportDefinitionAllowedScope|array<string, mixed>> $allowedScopes
     * @param array<int, ReportDefinitionAllowedFilter|array<string, mixed>> $allowedFilters
     */
    public function __construct(
        public string $id = '',
        public string $code = '',
        public ReportType|string|null $type = null,
        public ReportDefinitionStatus|string|null $status = null,
        public array $descriptionResources = [],
        public array $allowedIntervals = [],
        public array $allowedScopes = [],
        public array $allowedFilters = [],
    ) {
    }
}