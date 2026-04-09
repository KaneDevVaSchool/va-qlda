<?php

namespace App\Models;

use App\Models\Pivots\TeamUserPivot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $this->belongsToMany(User::class, 'team_user')
            ->using(TeamUserPivot::class)
            ->withPivot(['role', 'position', 'permissions'])
            ->withTimestamps();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'team_id');
    }
}
