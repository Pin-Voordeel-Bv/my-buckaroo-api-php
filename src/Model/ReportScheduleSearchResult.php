<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class ReportScheduleSearchResult
{
    /**
     * @param array<int, ReportSchedule|array<string, mixed>> $results
     * @param array<string, mixed> $links
     */
    public function __construct(
        public array $results = [],
        public ?string $usedContinuationToken = null,
        public ?string $nextContinuationToken = null,
        public array $links = [],
    ) {
    }

    public function hasNextPage(): bool
    {
        return $this->nextContinuationToken !== null && $this->nextContinuationToken !== '';
    }
}