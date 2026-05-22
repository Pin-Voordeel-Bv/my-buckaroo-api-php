<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

use PinVandaag\BuckarooAPI\Model\Enum\PayoutStatus;

final readonly class PayoutAttempt
{
    public function __construct(
        public PayoutStatus|string|null $payoutAttemptStatus = null,
        public string $fromIban = '',
        public string $toIban = '',
    ) {
    }
}