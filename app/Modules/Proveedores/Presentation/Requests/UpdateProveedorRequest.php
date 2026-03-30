<?php

namespace App\Modules\Proveedores\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProveedorRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'nombre' => ['required', 'string', 'max:150'],
            'nit' => [
                'required',
                'string',
                Rule::unique('proveedores', 'nit')->ignore($id),
            ],
            'telefono' => ['nullable', 'string'],
            'direccion' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
        ];
    }
}