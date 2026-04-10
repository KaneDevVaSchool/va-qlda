<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemModuleState extends Model
{
    protected $fillable = [
        'module_key',
        'maintenance',
        'message',
        'updated_by',
    ];

    protected $casts = [
        'maintenance' => 'boolean',
    ];
}
