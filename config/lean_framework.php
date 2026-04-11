<?php

/**
 * Nguồn khung Lean: database (lean_framework_configs) hoặc file JSON.
 * Không nhúng rubric trong PHP — xem database/data/lean_framework.json
 */
return [
    'json_path' => env('LEAN_FRAMEWORK_JSON', database_path('data/lean_framework.json')),
    'cache_key' => env('LEAN_FRAMEWORK_CACHE_KEY', 'lean_framework.payload'),
    'cache_ttl' => (int) env('LEAN_FRAMEWORK_CACHE_TTL', 3600),
];
