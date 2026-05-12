<?php

namespace App\Modules\PreciosCombustible\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrecioCombustibleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'precio' => ['required', 'numeric', 'gt:0'],
            'fecha_inicio' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'producto_id.required' => 'El producto es obligatorio.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'precio.required' => 'El precio es obligatorio.',
            'precio.gt' => 'El precio debe ser mayor a cero.',
            'fecha_inicio.date' => 'La fecha de inicio no es válida.',
        ];
    }
}