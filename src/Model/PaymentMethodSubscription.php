<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

use PinVandaag\BuckarooAPI\Model\Enum\PaymentMethodSubscriptionStatus;

final readonly class PaymentMethodSubscription
{
    /**
     * @param array<int, string> $currencies
     * @param array<string, mixed> $links
     */
    public function __construct(
        public array $currencies = [],
        public string $id = '',
        public string $serviceCode = '',
        public PaymentMethodSubscriptionStatus|string|null $status = null,
        public int $priority = 0,
        public ?string $activatedAt = null,
        public ?string $deactivatedAt = null,
        public string $createdAt = '',
        public array $links = [],
    ) {
    }
}