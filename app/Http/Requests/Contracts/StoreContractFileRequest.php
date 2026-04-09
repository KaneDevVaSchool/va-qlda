<?php

namespace App\Http\Requests\Contracts;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $maxKb = (int) config('ppms.upload_max_file_kb', 51200);

        return [
            'file' => ['required', 'file', 'max:'.$maxKb],
            'create_version' => ['sometimes', 'boolean'],
            'version_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
