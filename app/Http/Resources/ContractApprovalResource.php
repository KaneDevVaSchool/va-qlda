<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractApprovalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'approver_id' => $this->approver_id,
            'step' => $this->step,
            'status' => $this->status?->value,
            'approved_at' => $this->approved_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'approver' => $this->whenLoaded('approver', fn () => [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
                'email' => $this->approver->email,
            ]),
        ];
    }
}
