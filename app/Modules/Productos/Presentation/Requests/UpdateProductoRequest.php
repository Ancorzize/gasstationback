<?php

namespace App\Modules\Productos\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'codigo' => ['required', 'string', 'max:100', 'unique:productos,codigo,' . $id],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'marca_id' => ['nullable', 'integer', 'exists:marcas,id'],
            'categoria_producto_id' => ['required', 'integer', 'exists:categorias_producto,id'],
            'unidad_medida_id' => ['required', 'integer', 'exists:unidades_medida,id'],
            'precio_compra' => ['nullable', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'permite_decimal' => ['required', 'boolean'],
            'codigo_barras' => [ 'nullable', 'string', 'max:50', 'unique:productos,codigo_barras,' . $id,],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique' => 'El código ya está registrado.',
            'nombre.required' => 'El nombre es obligatorio.',
            'categoria_producto_id.required' => 'La categoría de producto es obligatoria.',
            'categoria_producto_id.exists' => 'La categoría de producto seleccionada no existe.',
            'unidad_medida_id.required' => 'La unidad de medida es obligatoria.',
            'unidad_medida_id.exists' => 'La unidad de medida seleccionada no existe.',
            'marca_id.exists' => 'La marca seleccionada no existe.',
            'precio_compra.numeric' => 'El precio de compra debe ser numérico.',
            'precio_compra.min' => 'El precio de compra no puede ser negativo.',
            'precio_venta.required' => 'El precio de venta es obligatorio.',
            'precio_venta.numeric' => 'El precio de venta debe ser numérico.',
            'precio_venta.min' => 'El precio de venta no puede ser negativo.',
            'permite_decimal.required' => 'Debe indicar si el producto permite decimales.',
            'permite_decimal.boolean' => 'El campo permite_decimal debe ser verdadero o falso.',
        ];
    }
}