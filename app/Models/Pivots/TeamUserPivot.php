<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamUserPivot extends Pivot
{
    protected $casts = [
        'permissions' => 'array',
    ];
}
