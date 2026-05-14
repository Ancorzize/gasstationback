<?php

namespace App\Modules\Ventas\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVentaCombustibleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'manguera_id' => ['required', 'integer', 'exists:mangueras,id'],
            'tipo_venta' => ['required', 'in:contado,credito,mixta'],
            'cliente_id' => ['nullable','required_if:tipo_venta,credito','integer','exists:clientes,id'],
            'metodo_pago' => ['nullable','required_unless:tipo_venta,credito','in:efectivo,transferencia,consignacion,datafono,qr'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'observacion' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'manguera_id.required' => 'La manguera es obligatoria.',
            'manguera_id.exists' => 'La manguera seleccionada no existe.',
            'tipo_venta.required' => 'El tipo de venta es obligatorio.',
            'tipo_venta.in' => 'El tipo de venta no es válido.',
            'metodo_pago.required' => 'El método de pago es obligatorio.',
            'metodo_pago.in' => 'El método de pago no es válido.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.gt' => 'El monto debe ser mayor a cero.',
        ];
    }
}