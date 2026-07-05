<?php

namespace App\Modules\Caja\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CerrarCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

   public function rules(): array
    {
        return [

            'cierres' => [
                'required',
                'array',
                'min:1'
            ],

            'cierres.*.caja_id' => [
                'required',
                'exists:cajas,id'
            ],

            'cierres.*.monto_real' => [
                'required',
                'numeric',
                'min:0'
            ],

            'observacion_cierre' => [
                'nullable',
                'string'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'monto_cierre_real.required' => 'El monto de cierre real es obligatorio.',
            'monto_cierre_real.numeric' => 'El monto de cierre real debe ser numérico.',
            'monto_cierre_real.min' => 'El monto de cierre real no puede ser negativo.',
        ];
    }
}