<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class LocalizedValue
{
    public function __construct(
        public string $locale = '',
        public string $value = '',
    ) {
    }
}