<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'contract_id',
        'version',
        'file_path',
        'note',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
