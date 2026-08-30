<?php

namespace App\Modules\TurnosIslero\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DevolverTurnoIsleroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'observacion_devolucion' => [
                'required',
                'string',
                'min:3',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'observacion_devolucion.required' =>
                'Debe indicar el motivo de la devolución.',

            'observacion_devolucion.min' =>
                'La observación debe tener al menos 3 caracteres.',
        ];
    }
}