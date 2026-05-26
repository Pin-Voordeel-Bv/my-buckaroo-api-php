<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class ReportDefinitionAllowedFilterValue
{
    /**
     * @param array<int, LocalizedValue|array<string, mixed>> $locales
     */
    public function __construct(
        public string $value = '',
        public array $locales = [],
    ) {
    }
}