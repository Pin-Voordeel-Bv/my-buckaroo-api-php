<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class ApiKeySearchResult
{
    /**
     * @param array<int, ApiKey|array<string, mixed>> $apiKeys
     * @param array<string, mixed> $links
     */
    public function __construct(
        public array $apiKeys = [],
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