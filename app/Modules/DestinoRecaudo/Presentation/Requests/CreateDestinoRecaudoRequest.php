<?php

namespace App\Modules\DestinoRecaudo\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDestinoRecaudoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => [
                'required',
                'string',
                'max:20',
                'unique:destinos_recaudo,codigo',
            ],

            'nombre' => [
                'required',
                'string',
                'max:100',
            ],

            'descripcion' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [

            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique' => 'El código ya existe.',

            'nombre.required' => 'El nombre es obligatorio.',

        ];
    }
}