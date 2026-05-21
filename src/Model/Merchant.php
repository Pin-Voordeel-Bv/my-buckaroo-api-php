<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

use PinVandaag\BuckarooAPI\Model\Enum\MerchantBranch;
use PinVandaag\BuckarooAPI\Model\Enum\MerchantStatus;
use PinVandaag\BuckarooAPI\Model\Enum\MerchantType;

final readonly class Merchant
{
    /**
     * @param array<string, mixed> $links
     */
    public function __construct(
        public string $name = '',
        public string $tradeName = '',
        public ?string $mcc = null,
        public MerchantBranch|string|null $branch = null,
        public MerchantStatus|string|null $status = null,
        public MerchantType|string|null $type = null,
        public string $defaultLanguage = '',
        public string $createdAt = '',
        public ?string $contractStartDate = null,
        public array $links = [],
    ) {
    }
}