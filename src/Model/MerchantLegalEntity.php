<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class MerchantLegalEntity
{
    public function __construct(
        public ?string $legalName = null,
        public ?string $cocNumber = null,
        public ?string $vatNumber = null,
        public ?MerchantAddress $address = null,
    ) {
    }
}