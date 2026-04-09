<?php

namespace App\Support\Vendors;

use Illuminate\Support\Facades\Cache;

class VendorListCache
{
    public static function bump(): void
    {
        Cache::forever('vendors:list_cache_bust', microtime(true));
    }

    public static function remember(string $innerKey, int $ttlSeconds, \Closure $callback): mixed
    {
        $bust = Cache::get('vendors:list_cache_bust', '1');
        $key = "vendors:list:{$bust}:{$innerKey}";

        return Cache::remember($key, $ttlSeconds, $callback);
    }
}
