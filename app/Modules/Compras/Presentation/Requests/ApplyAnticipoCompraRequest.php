<?php

namespace App\Modules\Compras\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyAnticipoCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'observacion' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'monto.required' => 'El monto a aplicar es obligatorio.',
            'monto.numeric' => 'El monto a aplicar debe ser un valor numérico.',
            'monto.gt' => 'El monto a aplicar debe ser mayor a cero.',
        ];
    }
}
