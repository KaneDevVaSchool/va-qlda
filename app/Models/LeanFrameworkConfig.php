<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeanFrameworkConfig extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'version',
        'payload',
        'is_active',
    ];

    protected $casts = [
        'payload' => 'array',
        'is_active' => 'boolean',
    ];
}
