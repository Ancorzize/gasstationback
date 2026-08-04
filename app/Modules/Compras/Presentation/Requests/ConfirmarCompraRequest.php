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
                'integer',
                'exists:cajas,id'
            ]

        ];
    }

    public function messages(): array
    {
        return [

            'caja_id.exists' =>
                'La caja seleccionada no existe.',

        ];
    }
}