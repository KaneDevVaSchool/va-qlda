<?php

namespace App\Http\Requests\Vendors;

use App\Enums\VendorTimelinePhase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorTimelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phase' => ['sometimes', Rule::enum(VendorTimelinePhase::class)],
            'occurred_at' => ['sometimes', 'date'],
            'performed_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:20000'],
            'is_current' => ['sometimes', 'boolean'],
        ];
    }
}
