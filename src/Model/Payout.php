<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

use PinVandaag\BuckarooAPI\Model\Enum\PayoutGroupingType;
use PinVandaag\BuckarooAPI\Model\Enum\PayoutStatus;

final readonly class Payout
{
    /**
     * @param array<int, PayoutAttempt|array<string, mixed>>|null $payoutAttempts
     * @param array<string, mixed> $links
     */
    public function __construct(
        public ?string $id = null,
        public string $accountId = '',
        public string $amount = '',
        public string $currency = '',
        public string $payoutDate = '',
        public string $description = '',
        public PayoutStatus|string|null $status = null,
        public PayoutGroupingType|string|null $payoutGroupingType = null,
        public string $payoutGroupingKey = '',
        public ?array $payoutAttempts = null,
        public array $links = [],
    ) {
    }
}