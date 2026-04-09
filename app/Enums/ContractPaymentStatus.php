<?php

namespace App\Enums;

enum ContractPaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Overdue = 'overdue';
}
