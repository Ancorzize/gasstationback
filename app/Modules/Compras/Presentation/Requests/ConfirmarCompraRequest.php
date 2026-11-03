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
                'nullable',
                'exists:cajas,id',
            ],
            'monto_anticipo' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'caja_id.exists' => 'La caja seleccionada no existe.',
            'monto_anticipo.numeric' => 'El monto del anticipo a aplicar debe ser un número válido.',
            'monto_anticipo.min' => 'El monto del anticipo a aplicar no puede ser negativo.',
        ];
    }
}
