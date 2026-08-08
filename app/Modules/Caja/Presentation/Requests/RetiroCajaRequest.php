<?php

namespace App\Modules\Caja\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RetiroCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'caja_id'=>[
                'required',
                'exists:cajas,id'
            ],

            'monto'=>[
                'required',
                'numeric',
                'min:0.01'
            ],

            'medio_pago'=>[
                'required',
                'in:efectivo,qr,transferencia,consignacion,datáfono,digital'
            ],

            'descripcion'=>[
                'nullable',
                'string',
                'max:255'
            ]

        ];
    }
}