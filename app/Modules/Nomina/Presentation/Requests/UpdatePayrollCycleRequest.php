<?php

namespace App\Modules\Nomina\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePayrollCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', 'unique:payroll_cycles,code,' . $id],
            'period_type' => ['required', 'string', Rule::in(['monthly', 'biweekly'])],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del ciclo es obligatorio.',
            'code.required' => 'El código del ciclo es obligatorio.',
            'code.unique' => 'El código del ciclo ya existe en el sistema.',
            'period_type.required' => 'El tipo de periodicidad es obligatorio.',
            'period_type.in' => 'El tipo de periodicidad debe ser "monthly" o "biweekly".',
        ];
    }
}
