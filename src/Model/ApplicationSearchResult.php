<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class ApplicationSearchResult
{
    /**
     * @param array<int, Application|array<string, mixed>> $applications
     * @param array<string, mixed> $links
     */
    public function __construct(
        public array $applications = [],
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