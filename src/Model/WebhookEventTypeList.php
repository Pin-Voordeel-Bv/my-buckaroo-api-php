<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class WebhookEventTypeList
{
    /**
     * @param array<int, WebhookEventType|array<string, mixed>> $eventTypes
     * @param array<string, mixed> $links
     */
    public function __construct(
        public array $eventTypes = [],
        public array $links = [],
    ) {
    }
}