<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Services\UserRbacService;

class ProjectPolicy
{
    public function __construct(
        private UserRbacService $rbac
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->rbac->can($user, 'projects.view');
    }

    public function view(User $user, Project $project): bool
    {
        return $this->rbac->can($user, 'projects.view');
    }

    public function create(User $user): bool
    {
        return $this->rbac->can($user, 'projects.create');
    }

    public function update(User $user, Project $project): bool
    {
        return $this->rbac->can($user, 'projects.update');
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->rbac->can($user, 'projects.delete');
    }
}
