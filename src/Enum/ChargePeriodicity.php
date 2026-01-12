<?php

namespace App\Enum;

enum ChargePeriodicity: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case BIANNUAL = 'biannual';
    case ANNUAL = 'annual';
    case ONE_TIME = 'one_time';
}
