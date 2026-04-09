<?php

namespace App\Http\Requests\Contracts;

use App\Models\ContractPayment;
use Illuminate\Foundation\Http\FormRequest;

class MarkPaymentPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $maxKb = (int) config('ppms.upload_max_file_kb', 51200);

        return [
            'paid_amount' => ['required', 'numeric', 'min:0.01'],
            'file' => ['nullable', 'file', 'max:'.$maxKb],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $payment = $this->route('payment');
            if (! $payment instanceof ContractPayment) {
                return;
            }
            $raw = $this->input('paid_amount');
            if ($raw === null || $raw === '') {
                return;
            }
            $remaining = (float) $payment->amount - (float) $payment->amount_paid;
            if ((float) $raw > $remaining + 0.0001) {
                $validator->errors()->add(
                    'paid_amount',
                    'The paid amount cannot exceed the remaining balance ('.number_format($remaining, 2, '.', '').').'
                );
            }
        });
    }
}
