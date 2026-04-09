<?php

namespace App\Services\ActivityFeed;

class ActivityKindResolver
{
    /**
     * Canonical kinds: created, updated, deleted, file_upload, status_change, assign_po, assign_project.
     */
    public static function resolve(string $action, ?array $old, ?array $new): string
    {
        $old = $old ?? [];
        $new = $new ?? [];

        if (str_ends_with($action, '.deleted')) {
            return 'deleted';
        }

        if ($action === 'project.duplicated') {
            return 'created';
        }

        if (str_contains($action, '.created')) {
            return 'created';
        }

        if ($action === 'file.uploaded') {
            return 'file_upload';
        }

        if (str_ends_with($action, '.status')) {
            return 'status_change';
        }

        if (in_array($action, [
            'contract.submitted',
            'contract.activated',
            'approval.approved',
            'approval.rejected',
            'contract.terminated',
            'project.archived',
            'payment.mark_paid',
        ], true)) {
            return 'status_change';
        }

        if ($action === 'contract.updated' && self::keyChanged($old, $new, 'followed_by_id')) {
            return 'assign_po';
        }

        if ($action === 'project.updated' && (
            self::keyChanged($old, $new, 'owner_id') || self::keyChanged($old, $new, 'team_id')
        )) {
            return 'assign_project';
        }

        if (in_array($action, ['project.updated', 'contract.updated', 'project.bulk_updated'], true)) {
            return 'updated';
        }

        return 'updated';
    }

    private static function keyChanged(array $old, array $new, string $key): bool
    {
        $hasOld = array_key_exists($key, $old);
        $hasNew = array_key_exists($key, $new);
        if (! $hasOld && ! $hasNew) {
            return false;
        }

        return ($old[$key] ?? null) != ($new[$key] ?? null);
    }
}
