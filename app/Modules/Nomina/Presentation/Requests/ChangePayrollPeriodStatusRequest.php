<?php

namespace App\Modules\Nomina\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangePayrollPeriodStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['open', 'closed', 'cancelled'])],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'El estado del período es obligatorio.',
            'status.in' => 'El estado del período debe ser open, closed o cancelled.',
        ];
    }
}
