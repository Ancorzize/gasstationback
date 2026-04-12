<?php

namespace App\Modules\MovimientosInventario\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovimientoInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'bodega_origen_id' => ['required', 'integer', 'exists:bodegas,id'],
            'bodega_destino_id' => ['required', 'integer', 'exists:bodegas,id', 'different:bodega_origen_id'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'observacion' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'producto_id.required' => 'El producto es obligatorio.',
            'producto_id.exists' => 'El producto seleccionado no existe.',

            'bodega_origen_id.required' => 'La bodega origen es obligatoria.',
            'bodega_origen_id.exists' => 'La bodega origen seleccionada no existe.',

            'bodega_destino_id.required' => 'La bodega destino es obligatoria.',
            'bodega_destino_id.exists' => 'La bodega destino seleccionada no existe.',
            'bodega_destino_id.different' => 'La bodega destino debe ser diferente a la bodega origen.',

            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.numeric' => 'La cantidad debe ser numérica.',
            'cantidad.gt' => 'La cantidad debe ser mayor a cero.',
        ];
    }
}