<?php

namespace App\Modules\Empleados\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeEmployeePaymentAccountStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }
}
