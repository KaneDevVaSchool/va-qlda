<?php

namespace App\Enums;

enum VendorResearchStatus: string
{
    case Researching = 'researching';
    case Shortlist = 'shortlist';
    case Rejected = 'rejected';
}
