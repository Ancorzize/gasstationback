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
        $user = $this->user();
        $requiereCombustible = $user && $user->can('vender_combustible');

        return [
            'estacion_id' => ['required', 'integer', 'exists:estaciones,id'],

            'mangueras' => $requiereCombustible ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'mangueras.*' => ['required', 'integer', 'exists:mangueras,id'],

            'observacion_apertura' => ['nullable', 'string'],

            'lecturas_iniciales' => ['nullable', 'array'],
            'lecturas_iniciales.*.manguera_id' => ['required_with:lecturas_iniciales', 'integer', 'exists:mangueras,id'],
            'lecturas_iniciales.*.lectura_inicial' => ['required_with:lecturas_iniciales', 'numeric', 'gte:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'estacion_id.required' => 'La estación es obligatoria.',
            'mangueras.required' => 'Debe seleccionar al menos una manguera.',
            'mangueras.min' => 'Debe seleccionar al menos una manguera.',
        ];
    }
}