<?php

namespace App\Modules\Inventarios\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportInventarioJsonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],

            'items.*.codigo_producto' => ['required', 'string'],
            'items.*.bodega_codigo' => ['required', 'string'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'items.*.observacion' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Debe enviar los items a importar.',
            'items.array' => 'El campo items debe ser un arreglo.',
            'items.min' => 'Debe enviar al menos un item.',

            'items.*.codigo_producto.required' => 'El código del producto es obligatorio.',
            'items.*.bodega_codigo.required' => 'El código de la bodega es obligatorio.',
            'items.*.cantidad.required' => 'La cantidad es obligatoria.',
            'items.*.cantidad.numeric' => 'La cantidad debe ser numérica.',
            'items.*.cantidad.gt' => 'La cantidad debe ser mayor a cero.',
        ];
    }
}