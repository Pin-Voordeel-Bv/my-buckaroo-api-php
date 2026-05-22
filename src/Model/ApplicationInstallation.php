<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

use PinVandaag\BuckarooAPI\Model\Enum\ApplicationInstallationStatus;

final readonly class ApplicationInstallation
{
    /**
     * @param array<string, mixed> $links
     */
    public function __construct(
        public string $merchantId = '',
        public string $merchantName = '',
        public string $id = '',
        public string $applicationId = '',
        public ApplicationInstallationStatus|string|null $status = null,
        public string $scopes = '',
        public string $createdAt = '',
        public array $links = [],
    ) {
    }
}