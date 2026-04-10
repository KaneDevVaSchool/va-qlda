<?php

namespace App\Http\Requests\Vendors;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['quality_score', 'delivery_score', 'communication_score'] as $k) {
            if ($this->has($k) && $this->input($k) === '') {
                $this->merge([$k => null]);
            }
        }
        if ($this->has('would_recommend') && $this->input('would_recommend') === '') {
            $this->merge(['would_recommend' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'numeric', 'between:1,5'],
            'summary' => ['nullable', 'string', 'max:255'],
            'context' => ['nullable', 'string', Rule::in([
                'procurement',
                'implementation',
                'support',
                'renewal',
                'other',
            ])],
            'quality_score' => ['nullable', 'numeric', 'between:1,5'],
            'delivery_score' => ['nullable', 'numeric', 'between:1,5'],
            'communication_score' => ['nullable', 'numeric', 'between:1,5'],
            'would_recommend' => ['nullable', 'boolean'],
            'body' => ['required', 'string', 'max:10000'],
        ];
    }
}
