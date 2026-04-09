<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractPaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $paid = (float) ($this->amount_paid ?? 0);
        $total = (float) $this->amount;

        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'due_date' => $this->due_date?->toDateString(),
            'amount' => (string) $this->amount,
            'amount_paid' => (string) $this->amount_paid,
            'amount_remaining' => (string) round(max(0, $total - $paid), 2),
            'status' => $this->status?->value,
            'paid_at' => $this->paid_at,
            'proof_file_id' => $this->proof_file_id,
            'proof_file' => $this->whenLoaded('proofFile', fn () => $this->proofFile ? [
                'id' => $this->proofFile->id,
                'file_name' => $this->proofFile->file_name,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
