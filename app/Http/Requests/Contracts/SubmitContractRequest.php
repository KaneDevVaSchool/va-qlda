<?php

namespace App\Http\Requests\Contracts;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'approvers' => ['required', 'array', 'min:1', 'max:20'],
            'approvers.*.user_id' => ['required', 'integer', Rule::exists(User::class, 'id')],
        ];
    }
}
