<?php

namespace App\Http\Requests\Vendors;

use App\Enums\VendorActiveStatus;
use App\Enums\VendorKind;
use App\Enums\VendorResearchStatus;
use App\Enums\VendorRiskLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach ([
            'risk_level',
            'contract_value',
            'estimated_cost',
            'reference_price',
            'score_price',
            'score_quality',
            'score_sla',
            'score_support',
            'fit_score',
        ] as $k) {
            if ($this->has($k) && $this->input($k) === '') {
                $this->merge([$k => null]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $kind = $this->input('kind', VendorKind::Active->value);

        return [
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::enum(VendorKind::class)],
            'status' => ['required', 'string', Rule::in($this->allowedStatusesForKind($kind))],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:64'],
            'website' => ['nullable', 'string', 'max:512'],
            'tax_code' => ['nullable', 'string', 'max:64'],
            'contact_info' => ['nullable', 'string'],
            'industry' => ['nullable', 'string', 'max:255'],
            'main_products' => ['nullable', 'string'],
            'contract_value' => ['nullable', 'numeric', 'min:0'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'reference_price' => ['nullable', 'numeric', 'min:0'],
            'score_price' => ['nullable', 'numeric', 'between:0,5'],
            'score_quality' => ['nullable', 'numeric', 'between:0,5'],
            'score_sla' => ['nullable', 'numeric', 'between:0,5'],
            'score_support' => ['nullable', 'numeric', 'between:0,5'],
            'risk_level' => ['nullable', Rule::enum(VendorRiskLevel::class)],
            'internal_note' => ['nullable', 'string'],
            'research_source' => ['nullable', 'string', 'max:64'],
            'research_note' => ['nullable', 'string'],
            'pros' => ['nullable', 'string'],
            'cons' => ['nullable', 'string'],
            'fit_score' => ['nullable', 'integer', 'between:0,100'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
            'products' => ['nullable', 'array'],
            'products.*.name' => ['required_with:products', 'string', 'max:255'],
            'products.*.description' => ['nullable', 'string'],
        ];
    }

    /**
     * @return list<string>
     */
    private function allowedStatusesForKind(string $kind): array
    {
        if ($kind === VendorKind::Research->value) {
            return array_map(static fn ($c) => $c->value, VendorResearchStatus::cases());
        }

        return array_map(static fn ($c) => $c->value, VendorActiveStatus::cases());
    }
}
