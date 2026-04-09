<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'vendor_id' => $this->vendor_id,
            'product_id' => $this->product_id,
            'department_id' => $this->department_id,
            'scope' => $this->scope,
            'status' => $this->status?->value,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'total_value' => (string) $this->total_value,
            'payment_cycle' => $this->payment_cycle?->value,
            'created_by' => $this->created_by,
            'approved_by' => $this->approved_by,
            'followed_by_id' => $this->followed_by_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'vendor' => VendorResource::make($this->whenLoaded('vendor')),
            'product' => ProductResource::make($this->whenLoaded('product')),
            'department' => DepartmentResource::make($this->whenLoaded('department')),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ]),
            'approver' => $this->whenLoaded('approver', fn () => $this->approver ? [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
                'email' => $this->approver->email,
            ] : null),
            'followed_by' => $this->whenLoaded('followedBy', fn () => $this->followedBy ? [
                'id' => $this->followedBy->id,
                'name' => $this->followedBy->name,
                'email' => $this->followedBy->email,
            ] : null),
            'versions' => ContractVersionResource::collection($this->whenLoaded('versions')),
            'files' => ContractFileResource::collection($this->whenLoaded('files')),
            'payments' => ContractPaymentResource::collection($this->whenLoaded('payments')),
            'approvals' => ContractApprovalResource::collection($this->whenLoaded('approvals')),
        ];
    }
}
