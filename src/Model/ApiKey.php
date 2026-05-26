<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class ApiKey
{
    /**
     * @param array<string, mixed> $links
     */
    public function __construct(
        public ?string $apiKey = null,
        public string $id = '',
        public string $key = '',
        public string $name = '',
        public string $maskedApiKey = '',
        public string $status = '',
        public string $scopes = '',
        public string $createdAt = '',
        public array $links = [],
    ) {
    }

    public function apiKeyHeader(): array
    {
        return [
            'X-API-KEY' => $this->key,
        ];
    }

    public function isActive(): bool
    {
        return strtolower($this->status) === 'active';
    }
}