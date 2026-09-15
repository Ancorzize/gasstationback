<?php

namespace App\Modules\Ventas\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => [
                'nullable',
                'integer',
                'exists:clientes,id'
            ],
            'tipo_venta' => [
                'sometimes',
                'in:contado,credito,mixta'
            ],
            'observacion' => [
                'nullable',
                'string'
            ],
            'detalles' => [
                'sometimes',
                'array',
                'min:1'
            ],
            'detalles.*.producto_id' => [
                'required_with:detalles',
                'integer',
                'exists:productos,id'
            ],
            'detalles.*.cantidad' => [
                'required_with:detalles',
                'numeric',
                'gt:0'
            ],
            'detalles.*.precio_unitario' => [
                'required_with:detalles',
                'numeric',
                'gte:0'
            ],
            'detalles.*.descuento' => [
                'nullable',
                'numeric',
                'gte:0'
            ],
            'detalles.*.iva' => [
                'nullable',
                'integer',
                'gte:0'
            ],
            'detalles.*.iva_valor' => [
                'nullable',
                'numeric',
                'gte:0'
            ],
            'detalles.*.soldicom' => [
                'nullable',
                'numeric',
                'gte:0'
            ],
            'detalles.*.sobre_tasa' => [
                'nullable',
                'numeric',
                'gte:0'
            ],
            'detalles.*.total' => [
                'required_with:detalles',
                'numeric',
                'gt:0'
            ],
            'pagos' => [
                'sometimes',
                'array',
            ],
            'pagos.*.metodo_pago' => [
                'required_with:pagos',
                'in:efectivo,transferencia,consignacion,datafono,qr'
            ],
            'pagos.*.monto' => [
                'required_with:pagos',
                'numeric',
                'gt:0'
            ],
            'pagos.*.observacion' => [
                'nullable',
                'string'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_venta.in' => 'El tipo de venta no es válido.',
            'detalles.array' => 'Los detalles deben ser un arreglo.',
            'detalles.min' => 'Debe agregar al menos un producto.',
            'detalles.*.producto_id.exists' => 'El producto no existe.',
            'detalles.*.cantidad.gt' => 'La cantidad debe ser mayor a cero.',
            'detalles.*.total.gt' => 'El total del producto debe ser mayor a cero.',
            'pagos.array' => 'Los pagos deben ser un arreglo.',
            'pagos.*.metodo_pago.required_with' => 'El método de pago es obligatorio.',
            'pagos.*.metodo_pago.in' => 'El método de pago no es válido.',
            'pagos.*.monto.required_with' => 'El monto del pago es obligatorio.',
            'pagos.*.monto.gt' => 'El monto del pago debe ser mayor a cero.',
        ];
    }
}
