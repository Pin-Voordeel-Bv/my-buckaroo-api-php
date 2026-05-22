<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class GlobalSearchResult
{
    /**
     * @param array<int, GlobalSearchItem|array<string, mixed>> $results
     */
    public function __construct(
        public array $results = [],
    ) {
    }
}