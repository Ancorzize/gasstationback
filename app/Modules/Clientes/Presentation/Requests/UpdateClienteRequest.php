<?php

namespace App\Modules\Clientes\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('cliente')?->id ?? $this->route('id');

        return [
            'nombre' => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'documento' => [
                'required',
                'string',
                'max:50',
                Rule::unique('clientes', 'documento')->ignore($id),
            ],
            'telefono_uno' => ['nullable', 'string', 'max:30'],
            'telefono_dos' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ];
    }
}