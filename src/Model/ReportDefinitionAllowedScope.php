<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

use PinVandaag\BuckarooAPI\Model\Enum\ReportScope;

final readonly class ReportDefinitionAllowedScope
{
    /**
     * @param array<int, LocalizedValue|array<string, mixed>> $locales
     */
    public function __construct(
        public ReportScope|string|null $scope = null,
        public array $locales = [],
    ) {
    }
}