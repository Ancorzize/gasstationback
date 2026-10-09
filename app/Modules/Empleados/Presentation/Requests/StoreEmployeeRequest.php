<?php

namespace App\Modules\Empleados\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'worker_code' => ['required', 'string', 'max:50', 'unique:employees,worker_code'],
            'identity_document_id' => ['nullable', 'string', 'max:20'],
            'document_number' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'other_names' => ['nullable', 'string', 'max:100'],
            'first_surname' => ['required', 'string', 'max:100'],
            'second_surname' => ['nullable', 'string', 'max:100'],
            'working_country_id' => ['nullable', 'string', 'max:20'],
            'work_city_id' => ['nullable', 'string', 'max:20'],
            'work_address' => ['required', 'string', 'max:255'],
            'date_entry' => ['required', 'date', 'date_format:Y-m-d'],
            'departure_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:date_entry'],
            'worker_type_id' => ['nullable', 'string', 'max:20'],
            'worker_subtype_id' => ['nullable', 'string', 'max:20'],
            'contract_type_id' => ['nullable', 'string', 'max:20'],
            'salary' => ['required', 'numeric', 'min:0'],
            'integral_salary' => ['nullable', 'boolean'],
            'high_risk_pension' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'worker_code.required' => 'El código del trabajador es obligatorio.',
            'worker_code.unique' => 'El código del trabajador ya existe en el sistema.',
            'document_number.required' => 'El número de documento es obligatorio.',
            'first_name.required' => 'El primer nombre es obligatorio.',
            'first_surname.required' => 'El primer apellido es obligatorio.',
            'work_address.required' => 'La dirección de trabajo es obligatoria.',
            'date_entry.required' => 'La fecha de ingreso es obligatoria.',
            'salary.required' => 'El salario base es obligatorio.',
        ];
    }
}
