<?php

namespace App\Services;

use App\Models\SystemModuleState;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class ModuleMaintenanceService
{
    protected const CACHE_KEY = 'ppms:module_maintenance:v1';

    protected const CACHE_TTL_SECONDS = 60;

    /**
     * @return list<string>
     */
    public function registeredModuleKeys(): array
    {
        return array_keys(config('ppms_modules.modules', []));
    }

    public function isValidModuleKey(string $key): bool
    {
        return in_array($key, $this->registeredModuleKeys(), true);
    }

    /**
     * @return array<string, array{maintenance: bool, message: string|null}>
     */
    public function allStates(): array
    {
        $keys = $this->registeredModuleKeys();
        $rows = SystemModuleState::query()
            ->whereIn('module_key', $keys)
            ->get()
            ->keyBy('module_key');

        $out = [];
        foreach ($keys as $k) {
            $row = $rows->get($k);
            $out[$k] = [
                'maintenance' => $row ? (bool) $row->maintenance : false,
                'message' => $row?->message,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, array{maintenance: bool, message: string|null}>
     */
    public function allStatesCached(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn () => $this->allStates());
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function isUnderMaintenance(string $moduleKey): bool
    {
        if (! $this->isValidModuleKey($moduleKey)) {
            return false;
        }

        return $this->allStatesCached()[$moduleKey]['maintenance'] ?? false;
    }

    /**
     * Block API access: module in maintenance and user is not bypassed.
     */
    public function shouldBlock(User $user, string $moduleKey): bool
    {
        if (! $this->isUnderMaintenance($moduleKey)) {
            return false;
        }

        $role = (string) ($user->role ?? '');
        $bypass = config('ppms_modules.maintenance_bypass_roles', ['admin']);

        return ! in_array($role, $bypass, true);
    }

    public function setState(string $moduleKey, bool $maintenance, ?string $message, ?User $updatedBy): SystemModuleState
    {
        if (! $this->isValidModuleKey($moduleKey)) {
            throw new \InvalidArgumentException('Unknown module key: '.$moduleKey);
        }

        $row = SystemModuleState::query()->firstOrNew(['module_key' => $moduleKey]);
        $row->maintenance = $maintenance;
        $row->message = $message;
        if ($updatedBy) {
            $row->updated_by = $updatedBy->id;
        }
        $row->save();

        $this->forgetCache();

        return $row;
    }
}
