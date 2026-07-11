<?php

namespace App\Modules\Compras\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmarCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'caja_id' => [
                'required',
                'integer',
                'exists:cajas,id'
            ]

        ];
    }

    public function messages(): array
    {
        return [

            'caja_id.required' =>
                'Debe seleccionar una caja.',

            'caja_id.exists' =>
                'La caja seleccionada no existe.',

        ];
    }
}