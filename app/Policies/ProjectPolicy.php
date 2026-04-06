<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /** Roles that can browse project lists and open project detail. */
    private function canBrowse(User $user): bool
    {
        return in_array($user->role, ['admin', 'pm', 'tl', 'hr', 'developer'], true);
    }

    /** Roles that can create / change project records (not delete). */
    private function canManage(User $user): bool
    {
        return in_array($user->role, ['admin', 'pm', 'tl'], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->canBrowse($user);
    }

    public function view(User $user, Project $project): bool
    {
        return $this->canBrowse($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, Project $project): bool
    {
        return in_array($user->role, ['admin', 'pm'], true);
    }
}
