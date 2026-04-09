<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tax_code' => $this->tax_code,
            'contact_info' => $this->contact_info,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
