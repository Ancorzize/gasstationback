<?php

namespace App\Modules\TurnosIslero\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarDescargueTurnoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto' => ['required', 'numeric', 'gt:0'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'monto.required' => 'El monto del descargue es obligatorio.',
            'monto.numeric' => 'El monto del descargue debe ser un valor numérico.',
            'monto.gt' => 'El monto del descargue debe ser mayor a cero.',
            'observacion.max' => 'La observación no puede superar los 255 caracteres.',
        ];
    }
}
