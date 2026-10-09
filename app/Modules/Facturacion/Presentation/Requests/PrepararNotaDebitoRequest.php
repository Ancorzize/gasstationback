<?php

namespace App\Modules\Facturacion\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrepararNotaDebitoRequest extends FormRequest
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
            'response_id' => ['nullable', 'integer', 'in:1,2,3,4'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'monto_incremento' => ['nullable', 'numeric', 'gt:0'],
            'items' => ['nullable', 'array'],
            'items.*.detalle_venta_id' => ['required_with:items', 'integer'],
            'items.*.cantidad_adicional' => ['required_with:items', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'venta_id.required' => 'El ID de la venta es obligatorio.',
            'venta_id.integer' => 'El ID de la venta debe ser un número entero.',
            'venta_id.exists' => 'La venta especificada no existe.',
            'tipo_ajuste.in' => 'El tipo de ajuste debe ser "valor" o "cantidad".',
            'response_id.in' => 'El tipo de discrepancia/respuesta debe ser 1 (Intereses), 2 (Gastos por cobrar), 3 (Cambio del valor) o 4 (Otros).',
            'motivo.max' => 'El motivo no debe exceder los 255 caracteres.',
            'monto_incremento.numeric' => 'El monto de incremento debe ser un valor numérico.',
            'monto_incremento.gt' => 'El monto de incremento debe ser un valor mayor a cero.',
            'items.array' => 'El campo items debe ser una lista de productos a incrementar.',
            'items.*.detalle_venta_id.required_with' => 'El ID del detalle de venta es obligatorio en cada ítem.',
            'items.*.cantidad_adicional.gt' => 'La cantidad adicional en cada ítem debe ser un valor mayor a cero.',
        ];
    }
}
