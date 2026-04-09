<?php

namespace App\Http\Resources;

use App\Models\Contract;
use App\Models\Project;
use App\Services\ActivityFeed\ActivityFeedService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityFeedItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\AuditLog $log */
        $log = $this->resource;
        $service = app(ActivityFeedService::class);

        $subjectType = $log->auditable_type === Project::class ? 'project' : 'contract';

        $tz = ActivityFeedService::TZ;
        $at = $log->created_at?->clone()->timezone($tz);

        return [
            'id' => $log->id,
            'subject_type' => $subjectType,
            'subject_id' => $log->auditable_id,
            'subject_label' => $log->subject_label,
            'action' => $log->action,
            'activity_kind' => $log->activity_kind,
            'kind_color' => $this->kindColor($log->activity_kind),
            'actor' => $log->user ? [
                'id' => $log->user->id,
                'name' => $log->user->name,
                'email' => $log->user->email,
            ] : null,
            'changes' => $service->buildChanges($log),
            'metadata' => [
                'ip' => $log->ip_address,
                'source' => $log->source ?? 'web',
            ],
            'metadata_extra' => $log->metadata,
            'read' => (bool) ($log->read_by_user ?? false),
            'created_at' => $log->created_at?->toIso8601String(),
            'created_at_vn' => $at ? $at->format('H:i').' - '.$at->format('d/m/Y') : null,
            'created_at_vn_date' => $at ? $at->format('Y-m-d') : null,
            'link' => $subjectType === 'project' ? '/projects/'.$log->auditable_id : '/contracts/'.$log->auditable_id,
        ];
    }

    private function kindColor(?string $kind): string
    {
        return match ($kind) {
            'created', 'file_upload' => 'green',
            'deleted' => 'red',
            default => 'yellow',
        };
    }
}
