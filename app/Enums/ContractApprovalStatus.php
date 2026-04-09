<?php

namespace App\Enums;

enum ContractApprovalStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
