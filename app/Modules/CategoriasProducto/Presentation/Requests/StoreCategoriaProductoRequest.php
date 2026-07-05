<?php

namespace App\Modules\CategoriasProducto\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoriaProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150', 'unique:categorias_producto,nombre'],
            'descripcion' => ['nullable', 'string'],
            'destino_recaudo_id' => ['required', 'integer']
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'La categoría ya está registrada.',
            'destino_recaudo_id.required' => 'el destino de recaudo es obligatorio'
        ];
    }
}