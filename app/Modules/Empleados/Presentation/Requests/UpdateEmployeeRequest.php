<?php

namespace App\Modules\Empleados\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'worker_code' => ['required', 'string', 'max:50', 'unique:employees,worker_code,' . $id],
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
}
