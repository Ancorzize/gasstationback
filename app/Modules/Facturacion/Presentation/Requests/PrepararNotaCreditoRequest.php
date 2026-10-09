<?php

namespace App\Modules\Facturacion\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrepararNotaCreditoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'venta_id' => ['required', 'integer', 'exists:ventas,id'],
            'tipo_ajuste' => ['nullable', 'string', 'in:valor,cantidad'],
            'response_id' => ['nullable', 'integer', 'in:1,2,3,4,5'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'monto_descuento' => ['nullable', 'numeric', 'gt:0'],
            'monto_ajuste' => ['nullable', 'numeric', 'gt:0'],
            'devolucion_inventario' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.detalle_venta_id' => ['required_with:items', 'integer', 'exists:detalle_ventas,id'],
            'items.*.cantidad' => ['required_with:items', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'venta_id.required' => 'El ID de la venta es obligatorio.',
            'venta_id.integer' => 'El ID de la venta debe ser un número entero.',
            'venta_id.exists' => 'La venta especificada no existe.',
            'tipo_ajuste.in' => 'El tipo de ajuste debe ser "valor" o "cantidad".',
            'response_id.in' => 'El tipo de discrepancia/respuesta debe ser 1 (Devolución parcial), 2 (Anulación de factura), 3 (Rebaja/Descuento), 4 (Ajuste de precio) o 5 (Otros).',
            'motivo.max' => 'El motivo no debe exceder los 255 caracteres.',
            'monto_descuento.numeric' => 'El monto de descuento debe ser un valor numérico.',
            'monto_descuento.gt' => 'El monto de descuento debe ser mayor a cero.',
            'monto_ajuste.numeric' => 'El monto de ajuste debe ser un valor numérico.',
            'monto_ajuste.gt' => 'El monto de ajuste debe ser mayor a cero.',
            'items.array' => 'Los ítems a ajustar deben ser una lista válida.',
            'items.*.detalle_venta_id.required_with' => 'El ID del detalle de venta es obligatorio para cada ítem.',
            'items.*.detalle_venta_id.exists' => 'Uno de los detalles de venta especificados no existe.',
            'items.*.cantidad.required_with' => 'La cantidad a acreditar es obligatoria.',
            'items.*.cantidad.numeric' => 'La cantidad a acreditar debe ser un valor numérico.',
            'items.*.cantidad.gt' => 'La cantidad a acreditar debe ser mayor a cero.',
        ];
    }
}
