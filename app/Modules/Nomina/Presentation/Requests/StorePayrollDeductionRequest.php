<?php

namespace App\Modules\Nomina\Presentation\Requests;

use App\Modules\Nomina\Domain\Enums\PayrollDeductionTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollDeductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_code' => ['required', 'string', Rule::enum(PayrollDeductionTypeEnum::class)],
            'concept_name' => ['required', 'string', 'max:150'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'base_amount' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'type_code.required' => 'El código de tipo de deducción es obligatorio.',
            'type_code.enum' => 'El tipo de deducción no es válido.',
            'concept_name.required' => 'El nombre del concepto de deducción es obligatorio.',
            'percentage.min' => 'El porcentaje debe ser mayor o igual a 0.',
            'percentage.max' => 'El porcentaje no puede ser mayor a 100.',
            'amount.required' => 'El valor de la deducción es obligatorio.',
            'amount.min' => 'El valor de la deducción no puede ser negativo.',
        ];
    }
}
