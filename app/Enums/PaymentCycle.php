<?php

namespace App\Enums;

enum PaymentCycle: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';
}
