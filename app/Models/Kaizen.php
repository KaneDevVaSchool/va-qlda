<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kaizen extends Model
{
    protected $fillable = [
        'submitter_id', 'week_start', 'problem', 'solution', 'outcome_measurable',
        'estimated_value',
        'status', 'tl_rating', 'score', 'reviewed_by',
    ];

    protected $casts = [
        'week_start' => 'date',
        'score' => 'decimal:2',
        'estimated_value' => 'decimal:2',
    ];

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitter_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
