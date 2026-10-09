<?php

namespace App\Modules\Nomina\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'payment_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:end_date'],
            'status' => ['nullable', 'string', Rule::in(['open', 'closed', 'cancelled'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del período es obligatorio.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'end_date.required' => 'La fecha de fin es obligatoria.',
            'end_date.after_or_equal' => 'La fecha de fin no puede ser anterior a la fecha de inicio.',
            'payment_date.required' => 'La fecha de pago es obligatoria.',
            'payment_date.after_or_equal' => 'La fecha de pago no puede ser anterior a la fecha de fin.',
            'status.in' => 'El estado del período debe ser open, closed o cancelled.',
        ];
    }
}
