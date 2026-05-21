<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class MerchantAddress
{
    public function __construct(
        public ?string $street = null,
        public ?string $houseNumber = null,
        public ?string $postalCode = null,
        public ?string $city = null,
        public ?string $country = null,
    ) {
    }
}