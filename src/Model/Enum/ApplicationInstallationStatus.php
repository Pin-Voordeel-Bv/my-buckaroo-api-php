<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum ApplicationInstallationStatus: string
{
    case Enabled = 'Enabled';
    case Disabled = 'Disabled';
}