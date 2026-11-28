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
            'cliente_id' => ['nullable','required_if:tipo_venta,credito','required_if:usar_anticipo,true','required_if:usar_anticipo,1','integer','exists:clientes,id'],
            'usar_anticipo' => ['nullable', 'boolean'],
            'monto_anticipo' => ['nullable', 'numeric', 'gt:0'],
            'metodo_pago' => ['nullable','required_unless:tipo_venta,credito','in:efectivo,transferencia,consignacion,datafono,qr'],
            'monto' => ['nullable', 'required_without:cantidad_galones', 'numeric', 'gt:0'],
            'cantidad_galones' => ['nullable', 'required_without:monto', 'numeric', 'gt:0'],
            'observacion' => ['nullable', 'string'],
            'factura_electronica' => ['nullable', 'boolean'],
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
            'monto.required_without' => 'Debe ingresar el monto o la cantidad de galones.',
            'monto.gt' => 'El monto debe ser mayor a cero.',
            'cantidad_galones.required_without' => 'Debe ingresar la cantidad de galones o el monto.',
            'cantidad_galones.gt' => 'La cantidad de galones debe ser mayor a cero.',
        ];
    }
}