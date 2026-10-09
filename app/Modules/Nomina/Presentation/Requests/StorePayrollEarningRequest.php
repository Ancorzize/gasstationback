<?php

namespace App\Modules\Nomina\Presentation\Requests;

use App\Modules\Nomina\Domain\Enums\PayrollEarningTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollEarningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_code' => ['required', 'string', Rule::enum(PayrollEarningTypeEnum::class)],
            'concept_name' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'unit_value' => ['nullable', 'numeric', 'min:0'],
            'is_taxable' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'type_code.required' => 'El código de tipo de devengado es obligatorio.',
            'type_code.enum' => 'El tipo de devengado no es válido.',
            'concept_name.required' => 'El nombre del concepto de devengado es obligatorio.',
            'amount.required' => 'El valor del devengado es obligatorio.',
            'amount.min' => 'El valor del devengado no puede ser negativo.',
        ];
    }
}
