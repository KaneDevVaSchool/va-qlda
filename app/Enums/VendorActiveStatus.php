<?php

namespace App\Enums;

enum VendorActiveStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Blacklist = 'blacklist';
}
