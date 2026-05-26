<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class ReportDefinitionAllowedFilter
{
    /**
     * @param array<int, LocalizedValue|array<string, mixed>> $descriptionResources
     * @param array<int, ReportDefinitionAllowedFilterValue|array<string, mixed>>|null $allowedValues
     */
    public function __construct(
        public string $name = '',
        public array $descriptionResources = [],
        public string $dataType = '',
        public string $controlType = '',
        public ?array $allowedValues = null,
    ) {
    }
}