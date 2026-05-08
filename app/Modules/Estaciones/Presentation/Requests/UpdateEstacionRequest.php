<?php

namespace App\Modules\Estaciones\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEstacionRequest extends FormRequest
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
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('estaciones', 'codigo')->ignore($id),
            ],
            'direccion' => ['nullable', 'string', 'max:255'],
        ];
    }
}