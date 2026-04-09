<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RbacRolePermission extends Model
{
    protected $fillable = [
        'role',
        'permission_key',
        'granted',
    ];

    protected $casts = [
        'granted' => 'boolean',
    ];
}
