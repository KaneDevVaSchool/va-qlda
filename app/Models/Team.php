<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    /** @var string */
    protected $connection = 'cms';

    public function getConnectionName(): ?string
    {
        if ($this->connection === 'cms'
            && app()->environment('testing')
            && config('database.default') === 'sqlite') {
            return config('database.default');
        }

        return $this->connection;
    }

    protected $fillable = [
        'name',
        'description',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')->withPivot('role')->withTimestamps();
    }
}
