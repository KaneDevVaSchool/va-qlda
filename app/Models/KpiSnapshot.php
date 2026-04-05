<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class KpiSnapshot extends Model
{
    protected $fillable = ['week_ending', 'entity_type', 'entity_id', 'metric_name', 'value', 'meta'];

    protected $casts = [
        'week_ending' => 'date',
        'value' => 'decimal:6',
        'meta' => 'array',
    ];

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
