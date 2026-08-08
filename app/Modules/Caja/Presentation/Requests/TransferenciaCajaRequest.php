<?php

namespace App\Modules\Caja\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferenciaCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'caja_origen_id'=>[
                'required',
                'exists:cajas,id'
            ],

            'caja_destino_id'=>[
                'required',
                'exists:cajas,id',
                'different:caja_origen_id'
            ],

            'monto'=>[
                'required',
                'numeric',
                'min:0.01'
            ],

            'descripcion'=>[
                'nullable',
                'string',
                'max:255'
            ]

        ];
    }
}