<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskDependency extends Model
{
    protected $fillable = ['successor_task_id', 'predecessor_task_id'];

    public function successor(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'successor_task_id');
    }

    public function predecessor(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'predecessor_task_id');
    }
}
