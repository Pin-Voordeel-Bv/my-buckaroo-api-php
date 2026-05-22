<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class GlobalSearchItem
{
    public function __construct(
        public string $resourceId = '',
        public string $resourceDescription = '',
        public string $resourceType = '',
        public string $fullText = '',
    ) {
    }
}