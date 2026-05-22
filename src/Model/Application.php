<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

use PinVandaag\BuckarooAPI\Model\Enum\ApplicationType;

final readonly class Application
{
    /**
     * @param array<string, mixed>|null $eventSubscription
     * @param array<string, mixed> $links
     */
    public function __construct(
        public ?string $clientSecret = null,
        public string $id = '',
        public string $name = '',
        public string $clientId = '',
        public ApplicationType|string|null $applicationType = null,
        public string $scopes = '',
        public ?array $eventSubscription = null,
        public string $createdAt = '',
        public array $links = [],
    ) {
    }
}