<?php

namespace App\Modules\Nomina\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollDeductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'concept_name' => ['sometimes', 'required', 'string', 'max:150'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'base_amount' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'concept_name.required' => 'El nombre del concepto de deducción es obligatorio.',
            'percentage.min' => 'El porcentaje debe ser mayor o igual a 0.',
            'percentage.max' => 'El porcentaje no puede ser mayor a 100.',
            'amount.required' => 'El valor de la deducción es obligatorio.',
            'amount.min' => 'El valor de la deducción no puede ser negativo.',
        ];
    }
}
