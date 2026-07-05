<?php

namespace App\Modules\Caja\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AbrirCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],

            'tipo_caja' => [
                'required',
                'in:efectivo,digital'
            ],

            'destino_recaudo_id' => [
                'required',
                'exists:destinos_recaudo,id'
            ],

            'monto_apertura' => [
                'required',
                'numeric',
                'min:0'
            ],

            'observacion_apertura' => [
                'nullable',
                'string'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'monto_apertura.required' => 'El monto de apertura es obligatorio.',
            'monto_apertura.numeric' => 'El monto de apertura debe ser numérico.',
            'monto_apertura.min' => 'El monto de apertura no puede ser negativo.',
        ];
    }
}