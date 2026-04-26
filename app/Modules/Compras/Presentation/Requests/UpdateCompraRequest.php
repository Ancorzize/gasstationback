<?php

namespace App\Modules\Compras\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
            'bodega_id' => ['required', 'integer', 'exists:bodegas,id'],
            'numero_documento' => ['nullable', 'string', 'max:100'],
            'fecha_compra' => ['required', 'date'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'tipo_pago' => ['required', 'in:efectivo,transferencia,consignacion,credito'],
            'impuesto' => ['nullable', 'numeric', 'min:0'],
            'soldicom' => ['required', 'numeric', 'gte:0'],
            'sobre_tasa' => ['required', 'numeric', 'gte:0'],
            'observacion' => ['nullable', 'string'],
            'numero_comprobante' => ['nullable', 'string'],

            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'detalles.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'detalles.*.costo_unitario' => ['required', 'numeric', 'gt:0'],
            'detalles.*.iva' => ['required', 'integer', 'min:0'],
            'detalles.*.soldicom' => ['required', 'numeric', 'gte:0'],
            'detalles.*.sobre_tasa' => ['required', 'numeric', 'gte:0'],
            'detalles.*.total' => ['required', 'numeric', 'gte:0'],
            'detalles.*.iva_valor' => ['required', 'numeric', 'gte:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'proveedor_id.required' => 'El proveedor es obligatorio.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'bodega_id.required' => 'La bodega es obligatoria.',
            'bodega_id.exists' => 'La bodega seleccionada no existe.',
            'fecha_compra.required' => 'La fecha de compra es obligatoria.',
            'tipo_pago.required' => 'El tipo de pago es obligatorio.',
            'tipo_pago.in' => 'El tipo de pago no es correcto',
            'soldicom.required' => 'El impuesto soldicom es requerido',
            'sobre_tasa.required' => 'La sobre tasa es requerido',
            'detalles.required' => 'Debe agregar al menos un detalle.',
            'detalles.array' => 'El detalle debe ser un arreglo.',
            'detalles.min' => 'Debe agregar al menos un producto.',
            'detalles.*.producto_id.required' => 'El producto es obligatorio.',
            'detalles.*.producto_id.exists' => 'El producto seleccionado no existe.',
            'detalles.*.cantidad.required' => 'La cantidad es obligatoria.',
            'detalles.*.cantidad.gt' => 'La cantidad debe ser mayor a cero.',
            'detalles.*.costo_unitario.required' => 'El costo unitario es obligatorio.',
            'detalles.*.costo_unitario.gt' => 'El costo unitario debe ser mayor a cero.',
            'detalles.*.iva' => 'El iva es requerido',
            'detalles.*.soldicom' => 'El soldicom es requerido',
            'detalles.*.sobre_tasa' => 'la sobre tasa es requerida',
            'detalles.*.total' => 'El total es requerido',
            'detalles.*.iva_valor' => 'El iva valor es requerido',
        ];
    }
}