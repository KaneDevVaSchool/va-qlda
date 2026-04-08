<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskParticipant extends Model
{
    public const ROLE_ASSIGNEE = 'assignee';

    public const ROLE_OWNER = 'owner';

    public const ROLE_FOLLOWER = 'follower';

    protected $fillable = [
        'task_id',
        'user_id',
        'role',
        'sort_order',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
