<?php

namespace App\Modules\Nomina\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollEarningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'concept_name' => ['sometimes', 'required', 'string', 'max:150'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'unit_value' => ['nullable', 'numeric', 'min:0'],
            'is_taxable' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'concept_name.required' => 'El nombre del concepto de devengado es obligatorio.',
            'amount.required' => 'El valor del devengado es obligatorio.',
            'amount.min' => 'El valor del devengado no puede ser negativo.',
        ];
    }
}
