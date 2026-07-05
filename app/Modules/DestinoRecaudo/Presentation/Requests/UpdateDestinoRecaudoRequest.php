<?php

namespace App\Modules\DestinoRecaudo\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDestinoRecaudoRequest extends FormRequest
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

                Rule::unique('destinos_recaudo','codigo')
                    ->ignore($this->route('id'))

            ],

            'nombre' => [

                'required',

                'string',

                'max:100'

            ],

            'descripcion' => [

                'nullable',

                'string'

            ],

        ];
    }
}