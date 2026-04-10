<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorReview extends Model
{
    protected $fillable = [
        'vendor_id',
        'user_id',
        'rating',
        'summary',
        'context',
        'quality_score',
        'delivery_score',
        'communication_score',
        'would_recommend',
        'body',
    ];

    protected $casts = [
        'rating' => 'decimal:1',
        'quality_score' => 'decimal:1',
        'delivery_score' => 'decimal:1',
        'communication_score' => 'decimal:1',
        'would_recommend' => 'boolean',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
