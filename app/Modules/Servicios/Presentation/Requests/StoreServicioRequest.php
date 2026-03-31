<?php

namespace App\Modules\Servicios\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServicioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:100', 'unique:servicios,codigo'],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'precio' => ['required', 'numeric', 'min:0'],
            'unidad_medida_id' => ['nullable', 'integer', 'exists:unidades_medida,id'],
            'permite_decimal' => ['required', 'boolean'],
            'duracion_minutos' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique' => 'El código ya está registrado.',
            'nombre.required' => 'El nombre es obligatorio.',
            'precio.required' => 'El precio es obligatorio.',
            'precio.numeric' => 'El precio debe ser numérico.',
            'precio.min' => 'El precio no puede ser negativo.',
            'unidad_medida_id.exists' => 'La unidad de medida seleccionada no existe.',
            'permite_decimal.required' => 'Debe indicar si el servicio permite decimales.',
            'permite_decimal.boolean' => 'El campo permite_decimal debe ser verdadero o falso.',
            'duracion_minutos.integer' => 'La duración debe ser un número entero.',
            'duracion_minutos.min' => 'La duración no puede ser negativa.',
        ];
    }
}