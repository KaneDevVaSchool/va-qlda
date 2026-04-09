<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    private function canBrowse(User $user): bool
    {
        return in_array($user->role, ['admin', 'pm', 'tl', 'hr', 'developer'], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->canBrowse($user);
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $this->canBrowse($user);
    }

    public function create(User $user): bool
    {
        return $this->canBrowse($user);
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $this->canBrowse($user);
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return in_array($user->role, ['admin', 'pm', 'tl'], true);
    }
}
