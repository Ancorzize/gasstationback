<?php

namespace App\Modules\Compras\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePagoCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_pago' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'metodo_pago' => ['required', 'string', 'max:30'],
            'observacion' => ['nullable', 'string'],
            'numero_comprobante' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_pago.required' => 'La fecha de pago es obligatoria.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric' => 'El monto debe ser numérico.',
            'monto.gt' => 'El monto debe ser mayor a cero.',
            'metodo_pago.required' => 'El método de pago es obligatorio.',
        ];
    }
}