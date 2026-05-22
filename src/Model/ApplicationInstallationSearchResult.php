<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class ApplicationInstallationSearchResult
{
    /**
     * @param array<int, ApplicationInstallation|array<string, mixed>> $installations
     * @param array<string, mixed> $links
     */
    public function __construct(
        public array $installations = [],
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