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
        $dsRequerido = filter_var($this->input('documento_soporte_electronico'), FILTER_VALIDATE_BOOLEAN);

        return [
            'fecha_gasto' => ['required', 'date'],
            'proveedor_id' => [
                $dsRequerido ? 'required' : 'nullable',
                'integer',
                'exists:proveedores,id'
            ],
            'categoria_gasto_id' => ['required', 'integer', 'exists:categorias_gasto,id'],
            'valor' => ['required', 'numeric', 'gt:0'],
            'descripcion' => ['required', 'string'],
            'caja_id' => ['required', 'integer'],
            'tipo_caja' => ['required', 'string'],
            'documento_soporte_electronico' => ['nullable', 'boolean'],
            'estado_documento_soporte' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_gasto.required' => 'La fecha del gasto es obligatoria.',
            'proveedor_id.required' => 'El proveedor es obligatorio cuando se requiere Documento Soporte Electrónico.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'categoria_gasto_id.required' => 'La categoría de gasto es obligatoria.',
            'categoria_gasto_id.exists' => 'La categoría de gasto seleccionada no existe.',
            'valor.required' => 'El valor es obligatorio.',
            'valor.numeric' => 'El valor debe ser numérico.',
            'valor.gt' => 'El valor debe ser mayor a cero.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'tipo_caja.required' => 'El tipo de caja es obligatorio',
        ];
    }
}