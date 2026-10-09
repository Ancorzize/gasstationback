<?php

namespace App\Modules\Empleados\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeePaymentAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method_id' => ['nullable', 'string', 'max:50'],
            'means_payment_id' => ['nullable', 'string', 'max:50'],
            'bank' => ['nullable', 'string', 'max:100'],
            'account_type' => ['nullable', 'string', 'max:50'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
