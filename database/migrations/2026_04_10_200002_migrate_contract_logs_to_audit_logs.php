<?php

use App\Models\AuditLog;
use App\Models\Contract;
use App\Services\ActivityFeed\ActivityKindResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contract_logs')) {
            return;
        }

        DB::table('contract_logs')->orderBy('id')->chunk(200, function ($rows) {
            foreach ($rows as $row) {
                if (AuditLog::query()
                    ->where('metadata->migrated_from_contract_log', $row->id)
                    ->exists()) {
                    continue;
                }

                $oldVal = $row->old_data;
                $newVal = $row->new_data;
                if (is_string($oldVal)) {
                    $oldVal = json_decode($oldVal, true);
                }
                if (is_string($newVal)) {
                    $newVal = json_decode($newVal, true);
                }

                $existsDup = AuditLog::query()
                    ->where('auditable_type', Contract::class)
                    ->where('auditable_id', $row->contract_id)
                    ->where('action', $row->action)
                    ->where('created_at', $row->created_at)
                    ->where('user_id', $row->user_id)
                    ->exists();

                if ($existsDup) {
                    continue;
                }

                $code = DB::table('contracts')->where('id', $row->contract_id)->value('code');
                $subjectLabel = $code ? (string) $code : 'Contract #'.$row->contract_id;

                AuditLog::query()->create([
                    'user_id' => $row->user_id,
                    'auditable_type' => Contract::class,
                    'auditable_id' => $row->contract_id,
                    'action' => $row->action,
                    'activity_kind' => ActivityKindResolver::resolve($row->action, is_array($oldVal) ? $oldVal : null, is_array($newVal) ? $newVal : null),
                    'old_values' => is_array($oldVal) ? $oldVal : null,
                    'new_values' => is_array($newVal) ? $newVal : null,
                    'ip_address' => null,
                    'source' => 'web',
                    'subject_label' => $subjectLabel,
                    'metadata' => ['migrated_from_contract_log' => (int) $row->id],
                    'created_at' => $row->created_at,
                    'updated_at' => $row->created_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        AuditLog::query()->whereNotNull('metadata')->chunkById(500, function ($logs) {
            foreach ($logs as $log) {
                $meta = $log->metadata;
                if (is_array($meta) && isset($meta['migrated_from_contract_log'])) {
                    $log->delete();
                }
            }
        });
    }
};
