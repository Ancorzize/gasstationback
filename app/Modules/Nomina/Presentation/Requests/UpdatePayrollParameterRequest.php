<?php

namespace App\Modules\Nomina\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollParameterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'smlmv' => ['sometimes', 'required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'transport_assistance' => ['sometimes', 'required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'max_smlmv_for_transport' => ['nullable', 'numeric', 'gt:0', 'regex:/^\d+(\.\d{1,2})?$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'smlmv.required' => 'El valor del SMLMV es obligatorio.',
            'smlmv.numeric' => 'El valor del SMLMV debe ser numérico.',
            'smlmv.min' => 'El valor del SMLMV no puede ser negativo.',
            'smlmv.regex' => 'El valor del SMLMV no puede tener más de 2 decimales.',
            'transport_assistance.required' => 'El valor del auxilio de transporte es obligatorio.',
            'transport_assistance.numeric' => 'El valor del auxilio de transporte debe ser numérico.',
            'transport_assistance.min' => 'El valor del auxilio de transporte no puede ser negativo.',
            'transport_assistance.regex' => 'El valor del auxilio de transporte no puede tener más de 2 decimales.',
            'max_smlmv_for_transport.gt' => 'El límite en SMLMV para auxilio debe ser mayor a cero.',
            'max_smlmv_for_transport.regex' => 'El límite en SMLMV no puede tener más de 2 decimales.',
        ];
    }
}
