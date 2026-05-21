<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class MerchantFeatures
{
    /**
     * The docs currently only show "shop" as example response field.
     *
     * @param array<string, mixed> $links
     */
    public function __construct(
        public ?string $shop = null,
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