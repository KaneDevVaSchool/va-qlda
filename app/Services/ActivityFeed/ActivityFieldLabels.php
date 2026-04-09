<?php

namespace App\Services\ActivityFeed;

use App\Models\Contract;
use App\Models\Project;

class ActivityFieldLabels
{
    /**
     * @return array<string, string> field key => translation key under activityFeed.fields
     */
    public static function forSubject(string $auditableType): array
    {
        if ($auditableType === Project::class) {
            return [
                'name' => 'project.name',
                'code' => 'project.code',
                'type' => 'project.type',
                'phase' => 'project.phase',
                'status' => 'project.status',
                'owner_id' => 'project.owner_id',
                'team_id' => 'project.team_id',
                'deadline' => 'project.deadline',
                'start_date' => 'project.start_date',
                'actual_start_date' => 'project.actual_start_date',
                'description' => 'project.description',
                'estimated_value' => 'project.estimated_value',
                'progress' => 'project.progress',
                'archived_at' => 'project.archived_at',
                'customer_name' => 'project.customer_name',
                'labels' => 'project.labels',
                'executor_user_ids' => 'project.executor_user_ids',
                'follower_user_ids' => 'project.follower_user_ids',
            ];
        }

        if ($auditableType === Contract::class) {
            return [
                'vendor_id' => 'contract.vendor_id',
                'product_id' => 'contract.product_id',
                'department_id' => 'contract.department_id',
                'block_id' => 'contract.block_id',
                'scope' => 'contract.scope',
                'status' => 'contract.status',
                'start_date' => 'contract.start_date',
                'end_date' => 'contract.end_date',
                'total_value' => 'contract.total_value',
                'payment_cycle' => 'contract.payment_cycle',
                'followed_by_id' => 'contract.followed_by_id',
                'approved_by' => 'contract.approved_by',
                'step' => 'contract.approval_step',
                'approver_id' => 'contract.approver_id',
                'file_id' => 'contract.file_id',
                'path' => 'contract.path',
                'disk' => 'contract.disk',
                'version' => 'contract.version',
                'paid_at' => 'contract.paid_at',
                'amount_paid' => 'contract.amount_paid',
                'proof_file_id' => 'contract.proof_file_id',
            ];
        }

        return [];
    }
}
