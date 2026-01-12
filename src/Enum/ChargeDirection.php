<?php

namespace App\Enum;

enum ChargeDirection: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';
}
