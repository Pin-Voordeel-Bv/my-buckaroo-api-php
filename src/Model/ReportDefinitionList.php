<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class ReportDefinitionList
{
    /**
     * @param array<int, ReportDefinition|array<string, mixed>> $reportDefinitions
     */
    public function __construct(
        public array $reportDefinitions = [],
    ) {
    }
}