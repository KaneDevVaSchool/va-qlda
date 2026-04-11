<?php

namespace App\Services;

use App\Models\LeanFrameworkConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class LeanFrameworkConfigService
{
    public function payload(bool $forgetCache = false): array
    {
        $key = config('lean_framework.cache_key', 'lean_framework.payload');
        $ttl = (int) config('lean_framework.cache_ttl', 3600);

        if ($forgetCache) {
            Cache::forget($key);
        }

        return Cache::remember($key, max(60, $ttl), function () {
            $row = LeanFrameworkConfig::query()->where('is_active', true)->orderByDesc('id')->first();
            if ($row && is_array($row->payload)) {
                return $this->validatePayload($row->payload);
            }

            $path = config('lean_framework.json_path');
            if (! $path || ! is_file($path)) {
                throw new \RuntimeException('Lean framework: không có bản ghi active và không tìm thấy file: '.(string) $path);
            }

            $json = File::get($path);

            return $this->validatePayload(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
        });
    }

    public function reloadFromFile(): void
    {
        $path = config('lean_framework.json_path');
        if (! is_file($path)) {
            throw new \RuntimeException('File không tồn tại: '.$path);
        }
        $payload = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        $this->validatePayload($payload);

        LeanFrameworkConfig::query()->update(['is_active' => false]);

        LeanFrameworkConfig::query()->updateOrCreate(
            ['slug' => 'default'],
            [
                'name' => 'Default Lean framework',
                'version' => $payload['version'] ?? null,
                'payload' => $payload,
                'is_active' => true,
            ]
        );

        Cache::forget(config('lean_framework.cache_key', 'lean_framework.payload'));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function validatePayload(array $payload): array
    {
        foreach (['pillar_weights', 'grade_bands', 'tracks', 'radar_criteria_map'] as $k) {
            if (! isset($payload[$k]) || ! is_array($payload[$k])) {
                throw new \InvalidArgumentException("Lean framework payload thiếu hoặc sai khóa: {$k}");
            }
        }

        return $payload;
    }
}
