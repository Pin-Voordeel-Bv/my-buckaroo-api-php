<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

use PinVandaag\BuckarooAPI\Model\Enum\AccountStatus;

final readonly class Account
{
    /**
     * @param array<string, mixed> $payoutSettings
     * @param array<string, mixed> $links
     */
    public function __construct(
        public string $id = '',
        public string $virtualIban = '',
        public string $currency = '',
        public string $balance = '',
        public string $availableBalance = '',
        public AccountStatus|string|null $status = null,
        public string $holder = '',
        public array $payoutSettings = [],
        public ?string $nextPayoutDate = null,
        public array $links = [],
    ) {
    }
}