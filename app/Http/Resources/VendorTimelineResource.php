<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorTimelineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phase' => $this->phase?->value ?? $this->phase,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'note' => $this->note,
            'is_current' => (bool) $this->is_current,
            'performed_by' => $this->whenLoaded('performedBy', fn () => $this->performedBy ? [
                'id' => $this->performedBy->id,
                'name' => $this->performedBy->name,
                'email' => $this->performedBy->email,
            ] : null),
        ];
    }
}
