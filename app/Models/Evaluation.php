<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    protected $fillable = [
        'period_type', 'period_label', 'person_id', 'p1', 'p2', 'p3',
        'total', 'grade', 'reviewer_id', 'status', 'adjustment_reason', 'adjustment_delta',
    ];

    protected $casts = [
        'p1' => 'decimal:2',
        'p2' => 'decimal:2',
        'p3' => 'decimal:2',
        'total' => 'decimal:2',
        'adjustment_delta' => 'decimal:2',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(User::class, 'person_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function peers(): HasMany
    {
        return $this->hasMany(EvaluationPeer::class);
    }
}
