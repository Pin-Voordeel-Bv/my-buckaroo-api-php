<?php

declare(strict_types=1);

namespace PinVandaag\BuckarooAPI\Model\Enum;

enum MerchantType: string
{
    case Regular = 'Regular';
    case Developer = 'Developer';
    case Demo = 'Demo';
    case Partner = 'Partner';
    case Supporter = 'Supporter';
    case Prospect = 'Prospect';
    case Marketplace = 'Marketplace';
    case MarketplaceSeller = 'MarketplaceSeller';
    case MarketplaceFundsAccount = 'MarketplaceFundsAccount';
    case Reseller = 'Reseller';
}