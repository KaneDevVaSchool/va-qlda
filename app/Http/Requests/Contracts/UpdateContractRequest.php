<?php

namespace App\Http\Requests\Contracts;

use App\Enums\PaymentCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'vendor_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'product_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'department_id' => ['sometimes', 'integer', 'exists:departments,id'],
            'block_id' => ['sometimes', 'nullable', 'integer', 'exists:blocks,id'],
            'scope' => ['nullable', 'string', 'max:65535'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date'],
            'total_value' => ['sometimes', 'numeric', 'min:0'],
            'payment_cycle' => ['sometimes', Rule::enum(PaymentCycle::class)],
            'followed_by_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $start = $this->input('start_date');
            $end = $this->input('end_date');
            if ($start && $end && $end < $start) {
                $validator->errors()->add('end_date', 'end_date must be on or after start_date.');
            }
        });
    }
}
