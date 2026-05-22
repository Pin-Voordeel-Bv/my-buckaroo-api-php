<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class InvoiceSearchResult
{
    /**
     * @param array<int, Invoice|array<string, mixed>> $invoices
     * @param array<string, mixed> $links
     */
    public function __construct(
        public array $invoices = [],
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