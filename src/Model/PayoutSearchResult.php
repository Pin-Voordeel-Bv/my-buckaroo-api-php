<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class PayoutSearchResult
{
    /**
     * @param array<int, Payout|array<string, mixed>> $payouts
     * @param array<string, mixed> $links
     */
    public function __construct(
        public array $payouts = [],
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