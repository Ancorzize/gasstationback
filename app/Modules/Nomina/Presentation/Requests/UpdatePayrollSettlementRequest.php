<?php

namespace App\Modules\Nomina\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePayrollSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'days_worked' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'calculated', 'closed', 'cancelled'])],
        ];
    }

    public function messages(): array
    {
        return [
            'days_worked.min' => 'Los días trabajados deben ser mayores a 0.',
            'status.in' => 'El estado debe ser draft, calculated, closed o cancelled.',
        ];
    }
}
