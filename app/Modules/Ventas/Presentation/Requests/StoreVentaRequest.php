<?php

namespace App\Modules\Ventas\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVentaRequest extends FormRequest
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
                'required',
                'in:contado,credito,mixta'
            ],

            'observacion' => [
                'nullable',
                'string'
            ],


            'detalles' => [
                'required',
                'array',
                'min:1'
            ],

            'detalles.*.producto_id' => [
                'required',
                'integer',
                'exists:productos,id'
            ],

            'detalles.*.cantidad' => [
                'required',
                'numeric',
                'gt:0'
            ],

            'detalles.*.precio_unitario' => [
                'required',
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
                'required',
                'numeric',
                'gt:0'
            ],

            'pagos.*.metodo_pago' => [
                'nullable',
                'required_unless:efectivo,transferencia,consignacion,datafono,qr'
            ],

            'pagos.*.monto' => [
                'nullable',
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

            'tipo_venta.required' => 'El tipo de venta es obligatorio.',
            'tipo_venta.in' => 'El tipo de venta no es válido.',

            'detalles.required' => 'Debe agregar productos.',
            'detalles.array' => 'Los detalles deben ser un arreglo.',
            'detalles.min' => 'Debe agregar al menos un producto.',

            'detalles.*.producto_id.required' => 'El producto es obligatorio.',
            'detalles.*.producto_id.exists' => 'El producto no existe.',

            'detalles.*.cantidad.required' => 'La cantidad es obligatoria.',
            'detalles.*.cantidad.numeric' => 'La cantidad debe ser numérica.',
            'detalles.*.cantidad.gt' => 'La cantidad debe ser mayor a cero.',

            'detalles.*.precio_unitario.required' => 'El precio unitario es obligatorio.',
            'detalles.*.precio_unitario.numeric' => 'El precio unitario debe ser numérico.',

            'detalles.*.total.required' => 'El total del producto es obligatorio.',

            'pagos.required' => 'Debe agregar pagos.',
            'pagos.array' => 'Los pagos deben ser un arreglo.',
            'pagos.min' => 'Debe registrar al menos un pago.',

            'pagos.*.metodo_pago.required' => 'El método de pago es obligatorio.',

            'pagos.*.monto.required' => 'El monto del pago es obligatorio.',
            'pagos.*.monto.numeric' => 'El monto debe ser numérico.',
            'pagos.*.monto.gt' => 'El monto debe ser mayor a cero.',
        ];
    }
}