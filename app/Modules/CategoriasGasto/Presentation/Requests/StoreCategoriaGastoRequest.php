<?php

namespace App\Modules\CategoriasGasto\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoriaGastoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150', 'unique:categorias_gasto,nombre'],
            'descripcion' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'El nombre ya está registrado.',
        ];
    }
}