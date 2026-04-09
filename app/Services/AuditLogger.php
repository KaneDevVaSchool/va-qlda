<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\Project;
use App\Services\ActivityFeed\ActivityKindResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public static function log(
        string $action,
        Model $model,
        ?array $old = null,
        ?array $fresh = null,
        ?int $actorUserId = null
    ): void {
        $req = request();
        $source = 'system';
        if (! app()->runningInConsole() && $req) {
            $path = (string) $req->path();
            $source = (str_starts_with($path, 'api') || $req->expectsJson()) ? 'api' : 'web';
        }

        $metadata = array_filter([
            'user_agent' => $req ? substr((string) $req->userAgent(), 0, 512) : null,
        ], static fn ($v) => $v !== null && $v !== '');

        AuditLog::create([
            'user_id' => $actorUserId ?? Auth::id(),
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'action' => $action,
            'activity_kind' => ActivityKindResolver::resolve($action, $old, $fresh),
            'old_values' => $old,
            'new_values' => $fresh,
            'ip_address' => $req?->ip(),
            'source' => $source,
            'subject_label' => self::resolveSubjectLabel($model, $old, $fresh),
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    private static function resolveSubjectLabel(Model $model, ?array $old, ?array $fresh): ?string
    {
        if ($model instanceof Project) {
            $name = $model->name ?? $model->code;

            return $name !== null && $name !== '' ? (string) $name : null;
        }

        if ($model instanceof Contract) {
            return $model->code ? (string) $model->code : null;
        }

        return null;
    }
}
