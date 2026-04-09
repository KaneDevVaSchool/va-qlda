<?php

namespace App\Http\Requests\Contracts;

use App\Enums\PaymentCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'scope' => ['nullable', 'string', 'max:65535'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'total_value' => ['required', 'numeric', 'min:0'],
            'payment_cycle' => ['required', Rule::enum(PaymentCycle::class)],
        ];
    }
}
