<?php

namespace App\Modules\Nomina\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeEmployeePayrollCycleStatusRequest extends FormRequest
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
