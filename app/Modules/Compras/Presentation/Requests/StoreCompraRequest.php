<?php

namespace App\Modules\Compras\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompraRequest extends FormRequest
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
            'tipo_pago' => ['required', 'in:contado,credito'],
            'impuesto' => ['required', 'numeric', 'gte:0'],
            'soldicom' => ['required', 'numeric', 'gte:0'],
            'observacion' => ['nullable', 'string'],

            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'detalles.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'detalles.*.costo_unitario' => ['required', 'numeric', 'gt:0'],
            'detalles.*.iva' => ['required', 'integer', 'min:0'],
            'detalles.*.soldicom' => ['required', 'numeric', 'gte:0'],
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
            'tipo_pago.in' => 'El tipo de pago debe ser contado o credito.',
            'impuesto.required' => 'El impuesto es requerido',
            'soldicom.required' => 'El impuesto soldicom es requerido',
            'detalles.required' => 'Debe agregar al menos un detalle.',
            'detalles.array' => 'El detalle debe ser un arreglo.',
            'detalles.min' => 'Debe agregar al menos un producto.',
            'detalles.*.producto_id.required' => 'El producto es obligatorio.',
            'detalles.*.producto_id.exists' => 'El producto seleccionado no existe.',
            'detalles.*.cantidad.required' => 'La cantidad es obligatoria.',
            'detalles.*.cantidad.gt' => 'La cantidad debe ser mayor a cero.',
            'detalles.*.costo_unitario.required' => 'El costo unitario es obligatorio.',
            'detalles.*.costo_unitario.gt' => 'El costo unitario debe ser mayor a cero.',
            'detalles.*.iva.required' => 'El iva es obligatorio.',
            'detalles.*.soldicom.required' => 'El impuesto soldicom es obligatorio.',
            'detalles.*.total.required' => 'El costo total es obligatorio.',
            'detalles.*.iva_valor.required' => 'El valor iva es obligatorio.'
        ];
    }
}