<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum PayoutStatementExportType: string
{
    case None = 'None';
    case Camt053 = 'Camt053';
    case MT940 = 'MT940';
}