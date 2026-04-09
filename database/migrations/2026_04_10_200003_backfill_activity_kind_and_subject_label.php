<?php

use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\Project;
use App\Services\ActivityFeed\ActivityKindResolver;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        AuditLog::query()
            ->whereNull('activity_kind')
            ->orderBy('id')
            ->chunkById(400, function ($logs) {
                foreach ($logs as $log) {
                    $log->activity_kind = ActivityKindResolver::resolve(
                        $log->action,
                        is_array($log->old_values) ? $log->old_values : null,
                        is_array($log->new_values) ? $log->new_values : null
                    );
                    $log->saveQuietly();
                }
            });

        AuditLog::query()
            ->where('auditable_type', Project::class)
            ->whereNull('subject_label')
            ->orderBy('id')
            ->chunkById(400, function ($logs) {
                foreach ($logs as $log) {
                    $p = Project::query()->find($log->auditable_id);
                    if ($p) {
                        $log->subject_label = $p->name ?? $p->code;
                        $log->saveQuietly();
                    }
                }
            });

        AuditLog::query()
            ->where('auditable_type', Contract::class)
            ->whereNull('subject_label')
            ->orderBy('id')
            ->chunkById(400, function ($logs) {
                foreach ($logs as $log) {
                    $c = Contract::query()->find($log->auditable_id);
                    if ($c) {
                        $log->subject_label = $c->code;
                        $log->saveQuietly();
                    }
                }
            });
    }

    public function down(): void
    {
        //
    }
};
