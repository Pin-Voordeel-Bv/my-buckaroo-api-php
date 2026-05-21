<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class Customer
{
    /**
     * @param array<string, mixed> $address
     * @param array<string, mixed> $organization
     * @param array<string, mixed>|null $metadata
     * @param array<string, mixed> $links
     */
    public function __construct(
        public ?string $id = null,
        public string $reference = '',
        public array $address = [],
        public ?string $title = null,
        public ?string $givenName = null,
        public ?string $familyName = null,
        public ?string $email = null,
        public ?string $phoneNumber = null,
        public ?string $dateOfBirth = null,
        public array $organization = [],
        public ?string $preferredLocale = null,
        public ?array $metadata = null,
        public array $links = [],
    ) {
    }
}