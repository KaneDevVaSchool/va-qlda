<?php

namespace App\Services;

use App\Models\RbacRolePermission;
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
        $roleMatrix = $this->baseRoleMatrix($role);

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
            $roleMatrix = $this->baseRoleMatrix($role);
            $row = [];
            foreach ($keys as $key) {
                $row[$key] = $this->roleAllows($roleMatrix, $key);
            }
            $out[$role] = $row;
        }

        return $out;
    }

    /**
     * Config fallback when no DB rows exist for this role.
     *
     * @return array<string, mixed>
     */
    public function baseRoleMatrix(string $role): array
    {
        $cfg = config('ppms_rbac.roles.'.$role, []);
        $rows = RbacRolePermission::query()->where('role', $role)->get();
        if ($rows->isEmpty()) {
            return $cfg;
        }

        $out = [];
        foreach ($rows as $row) {
            $out[$row->permission_key] = $row->granted;
        }

        if (($out['*'] ?? false) === true) {
            return ['*' => true];
        }

        // DB overrides; keys missing from saved matrix inherit from config (new modules).
        $keys = $this->allPermissionKeys();
        foreach ($keys as $k) {
            if (! array_key_exists($k, $out)) {
                $out[$k] = $this->roleAllows($cfg, $k);
            }
        }

        return $out;
    }

    /**
     * @param  array<string, bool>  $entries  permission_key => granted (full set from UI)
     */
    public function syncRoleMatrixFromUi(string $role, array $entries): void
    {
        RbacRolePermission::query()->where('role', $role)->delete();

        $keys = $this->allPermissionKeys();

        if ($role === 'admin') {
            $allTrue = true;
            foreach ($keys as $k) {
                if (! ($entries[$k] ?? false)) {
                    $allTrue = false;
                    break;
                }
            }
            if ($allTrue) {
                RbacRolePermission::query()->create([
                    'role' => 'admin',
                    'permission_key' => '*',
                    'granted' => true,
                ]);

                return;
            }
        }

        foreach ($keys as $key) {
            $granted = (bool) ($entries[$key] ?? false);
            RbacRolePermission::query()->create([
                'role' => $role,
                'permission_key' => $key,
                'granted' => $granted,
            ]);
        }
    }

    public function clearRoleMatrix(string $role): void
    {
        RbacRolePermission::query()->where('role', $role)->delete();
    }

    /**
     * @return list<string>
     */
    public function rolesWithCustomMatrix(): array
    {
        return RbacRolePermission::query()
            ->distinct()
            ->pluck('role')
            ->map(fn ($r) => (string) $r)
            ->values()
            ->all();
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

        return array_values(array_unique($keys));
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
