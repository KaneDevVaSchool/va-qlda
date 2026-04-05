<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KaizenWeeklyReminder extends Model
{
    protected $fillable = ['user_id', 'week_start', 'reminded_at', 'fulfilled_at', 'kaizen_id'];

    protected $casts = [
        'week_start' => 'date',
        'reminded_at' => 'datetime',
        'fulfilled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kaizen(): BelongsTo
    {
        return $this->belongsTo(Kaizen::class);
    }
}
