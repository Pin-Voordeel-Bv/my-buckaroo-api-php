<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

use PinVandaag\BuckarooAPI\Model\Enum\WebhookStatus;

final readonly class Webhook
{
    /**
     * @param array<int, string> $eventTypes
     * @param array<string, mixed> $links
     */
    public function __construct(
        public string $id = '',
        public string $url = '',
        public ?string $storeId = null,
        public array $eventTypes = [],
        public int $maxConcurrency = 0,
        public string $secret = '',
        public WebhookStatus|string|null $status = null,
        public string $createdAt = '',
        public array $links = [],
    ) {
    }
}