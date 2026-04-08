<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPermissionOverride;
use Carbon\Carbon;

class UserRbacService
{
    /**
     * @return array<string, bool>
     */
    public function effectivePermissions(User $user): array
    {
        $keys = $this->allPermissionKeys();
        $role = (string) ($user->role ?? 'developer');
        $roleMatrix = config('ppms_rbac.roles.'.$role, []);

        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $this->roleAllows($roleMatrix, $key);
        }

        $overrides = UserPermissionOverride::query()
            ->where('user_id', $user->id)
            ->get();

        foreach ($overrides as $o) {
            if (! $o->isActive()) {
                continue;
            }
            if (! in_array($o->permission_key, $keys, true)) {
                continue;
            }
            $out[$o->permission_key] = $o->granted;
        }

        return $out;
    }

    public function can(User $user, string $permissionKey): bool
    {
        $map = $this->effectivePermissions($user);

        return $map[$permissionKey] ?? false;
    }

    public function isPermissionAdmin(User $user): bool
    {
        $roles = config('ppms_rbac.permission_admin_roles', ['admin']);

        return in_array((string) $user->role, $roles, true);
    }

    /**
     * Matrix for UI: roles × permission keys.
     *
     * @return array<string, array<string, bool>>
     */
    public function roleMatrix(): array
    {
        $keys = $this->allPermissionKeys();
        $roles = array_keys(config('ppms_rbac.roles', []));
        $out = [];
        foreach ($roles as $role) {
            $roleMatrix = config('ppms_rbac.roles.'.$role, []);
            $row = [];
            foreach ($keys as $key) {
                $row[$key] = $this->roleAllows($roleMatrix, $key);
            }
            $out[$role] = $row;
        }

        return $out;
    }

    /**
     * @return list<array{permission_key: string, granted: bool, expires_at: string|null}>
     */
    public function overridesFor(User $user): array
    {
        return UserPermissionOverride::query()
            ->where('user_id', $user->id)
            ->orderBy('permission_key')
            ->get()
            ->map(fn (UserPermissionOverride $o) => [
                'permission_key' => $o->permission_key,
                'granted' => $o->granted,
                'expires_at' => $o->expires_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{permission_key: string, granted: bool, expires_at?: string|null}>  $rows
     */
    public function syncOverrides(User $user, array $rows): void
    {
        UserPermissionOverride::query()->where('user_id', $user->id)->delete();

        $keys = $this->allPermissionKeys();
        foreach ($rows as $row) {
            $key = $row['permission_key'] ?? '';
            if (! in_array($key, $keys, true)) {
                continue;
            }
            $expires = null;
            if (! empty($row['expires_at']) && is_string($row['expires_at'])) {
                $expires = Carbon::parse($row['expires_at']);
            }

            UserPermissionOverride::query()->create([
                'user_id' => $user->id,
                'permission_key' => $key,
                'granted' => (bool) ($row['granted'] ?? true),
                'expires_at' => $expires,
            ]);
        }
    }

    /**
     * @return list<string>
     */
    public function allPermissionKeys(): array
    {
        $modules = array_keys(config('ppms_rbac.modules', []));
        $actions = config('ppms_rbac.actions', ['view', 'create', 'update', 'delete']);
        $keys = [];
        foreach ($modules as $m) {
            foreach ($actions as $a) {
                $keys[] = $m.'.'.$a;
            }
        }

        return $keys;
    }

    /**
     * @param  array<string, mixed>  $roleMatrix
     */
    protected function roleAllows(array $roleMatrix, string $key): bool
    {
        if (($roleMatrix['*'] ?? false) === true) {
            return true;
        }

        return (bool) ($roleMatrix[$key] ?? false);
    }
}
