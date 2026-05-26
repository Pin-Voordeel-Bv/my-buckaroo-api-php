<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

final readonly class ReportScheduleFilter
{
    public function __construct(
        public string $name = '',
        public string $value = '',
    ) {
    }
}