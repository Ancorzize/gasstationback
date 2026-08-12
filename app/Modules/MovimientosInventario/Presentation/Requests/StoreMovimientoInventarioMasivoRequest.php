<?php

namespace App\Modules\MovimientosInventario\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovimientoInventarioMasivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bodega_origen_id' => [
                'required',
                'integer',
                'exists:bodegas,id',
            ],

            'bodega_destino_id' => [
                'required',
                'integer',
                'exists:bodegas,id',
                'different:bodega_origen_id',
            ],

            'observacion' => [
                'nullable',
                'string',
                'max:500',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.producto_id' => [
                'required',
                'integer',
                'exists:productos,id',
                'distinct',
            ],

            'items.*.cantidad' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'bodega_origen_id.required' =>
                'La bodega origen es obligatoria.',

            'bodega_origen_id.exists' =>
                'La bodega origen no existe.',

            'bodega_destino_id.required' =>
                'La bodega destino es obligatoria.',

            'bodega_destino_id.exists' =>
                'La bodega destino no existe.',

            'bodega_destino_id.different' =>
                'La bodega origen y destino no pueden ser iguales.',

            'items.required' =>
                'Debe enviar al menos un producto.',

            'items.array' =>
                'Los productos deben enviarse como una lista.',

            'items.min' =>
                'Debe enviar al menos un producto.',

            'items.*.producto_id.required' =>
                'El producto es obligatorio.',

            'items.*.producto_id.exists' =>
                'Uno de los productos no existe.',

            'items.*.producto_id.distinct' =>
                'No puede repetir el mismo producto en el movimiento.',

            'items.*.cantidad.required' =>
                'La cantidad es obligatoria.',

            'items.*.cantidad.numeric' =>
                'La cantidad debe ser numérica.',

            'items.*.cantidad.gt' =>
                'La cantidad debe ser mayor que cero.',
        ];
    }
}