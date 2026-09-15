<?php

namespace App\Modules\Cartera\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAbonoCarteraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'valor' => ['sometimes', 'numeric', 'gt:0'],
            'medio_pago' => ['sometimes', 'in:efectivo,transferencia,consignacion,datafono,qr'],
            'caja_id' => ['sometimes', 'nullable', 'integer', 'exists:cajas,id'],
            'observacion' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'valor.gt' => 'El valor del abono debe ser mayor a cero.',
            'medio_pago.in' => 'El medio de pago no es válido.',
            'caja_id.exists' => 'La caja seleccionada no existe.',
        ];
    }
}
