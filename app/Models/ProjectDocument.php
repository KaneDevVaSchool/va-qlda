<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectDocument extends Model
{
    public const TYPE_FOLDER = 'folder';

    public const TYPE_UPLOAD = 'upload';

    public const TYPE_LINK = 'link';

    protected $fillable = [
        'project_id', 'parent_id', 'doc_type', 'name', 'url',
        'disk', 'path', 'original_name', 'size_bytes', 'mime_type',
        'created_by', 'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'size_bytes' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectDocument::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProjectDocument::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function types(): array
    {
        return [
            self::TYPE_FOLDER,
            self::TYPE_UPLOAD,
            self::TYPE_LINK,
        ];
    }
}
