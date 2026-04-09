<?php

namespace App\Models;

use App\Enums\VendorTimelinePhase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorTimeline extends Model
{
    protected $fillable = [
        'vendor_id',
        'phase',
        'occurred_at',
        'performed_by_user_id',
        'note',
        'is_current',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'is_current' => 'boolean',
        'phase' => VendorTimelinePhase::class,
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }
}
