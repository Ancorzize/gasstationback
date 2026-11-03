<?php

namespace App\Modules\Compras\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaldoInicialProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proveedor_id' => [
                'required',
                'integer',
                'exists:proveedores,id',
            ],
            'numero_documento' => [
                'nullable',
                'string',
                'max:100',
            ],
            'fecha_documento' => [
                'required',
                'date',
            ],
            'valor' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'observacion' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'proveedor_id.required' => 'El proveedor es obligatorio.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'numero_documento.max' => 'El número de documento no puede superar los 100 caracteres.',
            'fecha_documento.required' => 'La fecha del documento es obligatoria.',
            'fecha_documento.date' => 'La fecha del documento no es válida.',
            'valor.required' => 'El valor es obligatorio.',
            'valor.numeric' => 'El valor debe ser numérico.',
            'valor.gt' => 'El valor del saldo inicial debe ser mayor a cero.',
            'observacion.max' => 'La observación no puede superar los 1000 caracteres.',
        ];
    }
}
