<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSupply extends Model
{
    protected $fillable = [
        'project_id', 'name', 'quantity', 'unit', 'notes', 'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
