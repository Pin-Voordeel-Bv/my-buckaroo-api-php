<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class WebhookSearchResult
{
    /**
     * @param array<int, string>|null $storeIds
     * @param array<int, string>|null $eventTypes
     * @param array<int, Webhook|array<string, mixed>> $webhooks
     * @param array<string, mixed> $links
     */
    public function __construct(
        public ?array $storeIds = null,
        public ?array $eventTypes = null,
        public array $webhooks = [],
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