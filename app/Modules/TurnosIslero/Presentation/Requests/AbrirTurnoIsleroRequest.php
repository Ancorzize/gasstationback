<?php

namespace App\Modules\TurnosIslero\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AbrirTurnoIsleroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estacion_id' => ['required', 'integer', 'exists:estaciones,id'],
            'observacion_apertura' => ['nullable', 'string'],

            'lecturas_iniciales' => ['nullable', 'array'],
            'lecturas_iniciales.*.manguera_id' => ['required_with:lecturas_iniciales', 'integer', 'exists:mangueras,id'],
            'lecturas_iniciales.*.lectura_inicial' => ['required_with:lecturas_iniciales', 'numeric', 'gte:0'],
        ];
    }
}