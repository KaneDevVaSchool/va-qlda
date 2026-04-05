<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpmsNotification extends Model
{
    protected $table = 'ppms_notifications';

    protected $fillable = ['type', 'recipient_id', 'channel', 'payload', 'read_at', 'sent_at'];

    protected $casts = [
        'payload' => 'array',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
