<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model;

use PinVandaag\BuckarooAPI\Model\Enum\PayoutConsolidation;
use PinVandaag\BuckarooAPI\Model\Enum\PayoutGroupingType;
use PinVandaag\BuckarooAPI\Model\Enum\PayoutInterval;
use PinVandaag\BuckarooAPI\Model\Enum\PayoutStatementExportType;
use PinVandaag\BuckarooAPI\Model\Enum\PayoutStatementIbanVariant;

final readonly class AccountPayoutSettings
{
    /**
     * @param array<int, int> $payoutDays
     */
    public function __construct(
        public string $payoutFromIban = '',
        public ?string $payoutIban = null,
        public PayoutInterval|string|null $payoutInterval = null,
        public ?string $payoutCutoffTime = null,
        public array $payoutDays = [],
        public PayoutGroupingType|string|null $grouping = null,
        public PayoutConsolidation|string|null $consolidation = null,
        public PayoutStatementIbanVariant|string|null $payoutStatementIbanVariant = null,
        public PayoutStatementExportType|string|null $payoutStatementExportType = null,
        public ?bool $instantPayout = null,
    ) {
    }
}