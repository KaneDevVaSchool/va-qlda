<?php

namespace App\Http\Requests\Vendors;

use App\Enums\VendorTimelinePhase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorTimelineRequest extends FormRequest
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
            'phase' => ['required', Rule::enum(VendorTimelinePhase::class)],
            'occurred_at' => ['required', 'date'],
            'performed_by_user_id' => ['nullable', 'integer', 'exists:cms.users,id'],
            'note' => ['nullable', 'string', 'max:20000'],
            'is_current' => ['sometimes', 'boolean'],
        ];
    }
}
