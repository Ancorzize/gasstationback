<?php

namespace App\Modules\Estaciones\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'codigo' => ['required', 'string', 'max:50', 'unique:estaciones,codigo'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ];
    }
}