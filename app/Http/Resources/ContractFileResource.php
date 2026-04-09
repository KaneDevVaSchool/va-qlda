<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractFileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'file_type' => $this->file_type,
            'uploaded_by' => $this->uploaded_by,
            'created_at' => $this->created_at,
            'uploader' => $this->whenLoaded('uploader', fn () => [
                'id' => $this->uploader->id,
                'name' => $this->uploader->name,
                'email' => $this->uploader->email,
            ]),
        ];
    }
}
