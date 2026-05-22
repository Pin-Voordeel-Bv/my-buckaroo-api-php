<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class AccountSearchResult
{
    /**
     * @param array<int, Account|array<string, mixed>> $accounts
     * @param array<string, mixed> $links
     */
    public function __construct(
        public array $accounts = [],
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