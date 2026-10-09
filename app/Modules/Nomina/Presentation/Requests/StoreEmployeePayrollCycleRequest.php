<?php

namespace App\Modules\Nomina\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeePayrollCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payroll_cycle_id' => ['required', 'integer', 'exists:payroll_cycles,id'],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'payroll_cycle_id.required' => 'El ciclo de nómina es obligatorio.',
            'payroll_cycle_id.exists' => 'El ciclo de nómina seleccionado no existe.',
            'start_date.required' => 'La fecha de inicio de asignación es obligatoria.',
            'end_date.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
        ];
    }
}
