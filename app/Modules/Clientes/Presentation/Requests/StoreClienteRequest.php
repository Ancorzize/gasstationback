<?php

namespace App\Modules\Clientes\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'documento' => ['required', 'string', 'max:50', 'unique:clientes,documento'],
            'telefono_uno' => ['nullable', 'string', 'max:30'],
            'telefono_dos' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ];
    }
}