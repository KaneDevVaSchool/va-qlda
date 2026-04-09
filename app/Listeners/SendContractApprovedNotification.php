<?php

namespace App\Listeners;

use App\Events\ContractApproved;
use App\Models\PpmsNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendContractApprovedNotification implements ShouldQueue
{
    public function handle(ContractApproved $event): void
    {
        $contract = $event->contract->loadMissing('creator:id,name');
        if (! $contract->created_by) {
            return;
        }

        PpmsNotification::query()->create([
            'type' => 'contract.approved',
            'recipient_id' => $contract->created_by,
            'channel' => 'in_app',
            'payload' => [
                'contract_id' => $contract->id,
                'code' => $contract->code,
                'message' => 'Your contract '.$contract->code.' is approved and active.',
            ],
            'sent_at' => now(),
        ]);
    }
}
