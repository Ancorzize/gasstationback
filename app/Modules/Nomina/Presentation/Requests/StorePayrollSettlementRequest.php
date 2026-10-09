<?php

namespace App\Modules\Nomina\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'days_worked' => ['required', 'integer', 'min:1'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'calculated', 'closed', 'cancelled'])],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'El empleado es obligatorio.',
            'employee_id.exists' => 'El empleado seleccionado no existe.',
            'days_worked.required' => 'Los días trabajados son obligatorios.',
            'days_worked.min' => 'Los días trabajados deben ser mayores a 0.',
            'status.in' => 'El estado debe ser draft, calculated, closed o cancelled.',
        ];
    }
}
