<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInfo extends Model
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

    protected $table = 'user_info';

    protected $fillable = [
        'user_id',
        'code',
        'gender',
        'birthdate',
        'birth_place',
        'national',
        'religion',
        'hometown',
        'identity',
        'identity_date',
        'identity_place',
        'tax_code',
        'social_insurance_number',
        'phone',
        'address',
        'household',
        'bank_account',
        'bank',
        'start_working_date',
        'working_place',
        'note',
        'company_name',
        'department_name',
        'unit_name',
        'headquarter_name',
        'position_name',
        'concurrent_position_name',
        'department_id',
        'company_id',
        'health_insurance_code',
        'unemployment_insurance_number',
    ];

    protected $casts = [
        'birthdate' => 'datetime',
        'identity_date' => 'datetime',
        'start_working_date' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
