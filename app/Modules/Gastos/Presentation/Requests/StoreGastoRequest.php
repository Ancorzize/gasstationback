<?php

namespace App\Modules\Gastos\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGastoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_gasto' => ['required', 'date'],
            'proveedor_id' => ['nullable', 'integer', 'exists:proveedores,id'],
            'categoria_gasto_id' => ['required', 'integer', 'exists:categorias_gasto,id'],
            'valor' => ['required', 'numeric', 'gt:0'],
            'descripcion' => ['required', 'string'],
            'caja_id' => ['required','integer'],
            'tipo_caja'=> ['required','string'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_gasto.required' => 'La fecha del gasto es obligatoria.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'categoria_gasto_id.required' => 'La categoría de gasto es obligatoria.',
            'categoria_gasto_id.exists' => 'La categoría de gasto seleccionada no existe.',
            'valor.required' => 'El valor es obligatorio.',
            'valor.numeric' => 'El valor debe ser numérico.',
            'valor.gt' => 'El valor debe ser mayor a cero.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'tipo_caja.required'=>'El tipo de caja es obligatorio',
        ];
    }
}