<?php

namespace App\Modules\Bodegas\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBodegaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'nombre' => ['required', 'string', 'max:150'],
            'codigo' => ['required', 'string', 'max:100', 'unique:bodegas,codigo,' . $id],
            'descripcion' => ['nullable', 'string'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'responsable_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_principal' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique' => 'El código ya está registrado.',
            'responsable_id.exists' => 'El responsable seleccionado no existe.',
            'is_principal.required' => 'Debe indicar si la bodega es principal.',
            'is_principal.boolean' => 'El campo is_principal debe ser verdadero o falso.',
        ];
    }
}