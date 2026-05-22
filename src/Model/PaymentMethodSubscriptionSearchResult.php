<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class PaymentMethodSubscriptionSearchResult
{
    /**
     * @param array<int, PaymentMethodSubscription|array<string, mixed>> $subscriptions
     * @param array<string, mixed> $links
     */
    public function __construct(
        public array $subscriptions = [],
        public array $links = [],
    ) {
    }
}