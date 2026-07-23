<?php

namespace App\Modules\Cartera\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAbonoCarteraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'fecha_abono' => ['required', 'date'],
            'valor' => ['required', 'numeric', 'gt:0'],
            'medio_pago' => ['required', 'in:efectivo,transferencia,consignacion,datafono,qr'],
            'observacion' => ['nullable', 'string'],
            'caja_id' => ['required','integer','exists:cajas,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' => 'El cliente es obligatorio.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'fecha_abono.required' => 'La fecha del abono es obligatoria.',
            'valor.required' => 'El valor del abono es obligatorio.',
            'valor.gt' => 'El valor del abono debe ser mayor a cero.',
            'medio_pago.required' => 'El medio de pago es obligatorio.',
            'medio_pago.in' => 'El medio de pago no es válido.',
        ];
    }
}