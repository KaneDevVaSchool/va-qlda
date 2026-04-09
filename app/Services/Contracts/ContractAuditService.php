<?php

namespace App\Services\Contracts;

use App\Models\Contract;
use App\Models\ContractLog;
use App\Services\AuditLogger;

class ContractAuditService
{
    public function log(
        Contract $contract,
        string $action,
        ?array $oldData,
        ?array $newData,
        ?int $userId
    ): void {
        ContractLog::query()->create([
            'contract_id' => $contract->id,
            'action' => $action,
            'old_data' => $oldData,
            'new_data' => $newData,
            'user_id' => $userId,
            'created_at' => now(),
        ]);

        AuditLogger::log($action, $contract, $oldData, $newData, $userId);
    }
}
