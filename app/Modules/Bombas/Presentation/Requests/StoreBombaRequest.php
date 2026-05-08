<?php

namespace App\Modules\Bombas\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBombaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estacion_id' => ['required', 'integer', 'exists:estaciones,id'],
            'nombre' => ['required', 'string', 'max:150'],
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('bombas', 'codigo')
                    ->where('estacion_id', $this->input('estacion_id')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'estacion_id.required' => 'La estación es obligatoria.',
            'estacion_id.exists' => 'La estación seleccionada no existe.',
            'nombre.required' => 'El nombre de la bomba es obligatorio.',
            'codigo.required' => 'El código de la bomba es obligatorio.',
            'codigo.unique' => 'Ya existe una bomba con ese código en la estación seleccionada.',
        ];
    }
}