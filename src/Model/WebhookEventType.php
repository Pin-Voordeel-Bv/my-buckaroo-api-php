<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class WebhookEventType
{
    /**
     * @param array<int, LocalizedValue|array<string, mixed>> $description
     */
    public function __construct(
        public string $code = '',
        public array $description = [],
    ) {
    }
}