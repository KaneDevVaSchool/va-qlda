<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => (string) $this->rating,
            'summary' => $this->summary,
            'context' => $this->context,
            'quality_score' => $this->quality_score !== null ? (string) $this->quality_score : null,
            'delivery_score' => $this->delivery_score !== null ? (string) $this->delivery_score : null,
            'communication_score' => $this->communication_score !== null ? (string) $this->communication_score : null,
            'would_recommend' => $this->would_recommend,
            'body' => $this->body,
            'created_at' => $this->created_at?->toIso8601String(),
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author->id,
                'name' => $this->author->name,
                'email' => $this->author->email,
            ]),
        ];
    }
}
