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
            'kind' => $this->kind?->value ?? $this->kind,
            'status' => $this->status,
            'legal_name' => $this->legal_name,
            'country' => $this->country,
            'website' => $this->website,
            'tax_code' => $this->tax_code,
            'contact_info' => $this->contact_info,
            'industry' => $this->industry,
            'main_products' => $this->main_products,
            'contract_value' => $this->contract_value !== null ? (string) $this->contract_value : null,
            'estimated_cost' => $this->estimated_cost !== null ? (string) $this->estimated_cost : null,
            'reference_price' => $this->reference_price !== null ? (string) $this->reference_price : null,
            'vendor_score' => $this->vendor_score !== null ? (string) $this->vendor_score : null,
            'score_price' => $this->score_price !== null ? (string) $this->score_price : null,
            'score_quality' => $this->score_quality !== null ? (string) $this->score_quality : null,
            'score_sla' => $this->score_sla !== null ? (string) $this->score_sla : null,
            'score_support' => $this->score_support !== null ? (string) $this->score_support : null,
            'risk_level' => $this->risk_level?->value ?? $this->risk_level,
            'internal_note' => $this->internal_note,
            'research_source' => $this->research_source,
            'research_note' => $this->research_note,
            'pros' => $this->pros,
            'cons' => $this->cons,
            'fit_score' => $this->fit_score,
            'review_rating_avg' => $this->review_rating_avg !== null ? (string) $this->review_rating_avg : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'departments' => DepartmentResource::collection($this->whenLoaded('departments')),
            'products' => VendorProductResource::collection($this->whenLoaded('products')),
            'reviews' => VendorReviewResource::collection($this->whenLoaded('reviews')),
            'timelines' => VendorTimelineResource::collection($this->whenLoaded('timelines')),
            'contracts' => VendorContractSummaryResource::collection($this->whenLoaded('contracts')),
        ];
    }
}
