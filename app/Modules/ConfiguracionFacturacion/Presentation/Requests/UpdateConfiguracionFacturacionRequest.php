<?php

namespace App\Modules\ConfiguracionFacturacion\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfiguracionFacturacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proveedor_activo' => ['required', 'string', 'max:50'],
            'ambiente' => ['required', 'string', 'in:sandbox,produccion'],
            'facturacion_electronica_activa' => ['required', 'boolean'],
            'reintentos_automaticos' => ['required', 'boolean'],
            'max_reintentos' => ['required', 'integer', 'min:1', 'max:10'],
            'configuracion_empresa_id' => ['nullable', 'integer', 'exists:configuracion_empresa,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'proveedor_activo.required' => 'El proveedor activo es obligatorio.',
            'ambiente.required' => 'El ambiente es obligatorio.',
            'ambiente.in' => 'El ambiente debe ser sandbox o produccion.',
            'facturacion_electronica_activa.required' => 'Debe indicar si la facturación electrónica está activa.',
            'facturacion_electronica_activa.boolean' => 'El campo facturación electrónica activa debe ser verdadero o falso.',
            'reintentos_automaticos.required' => 'Debe indicar si se permiten reintentos automáticos.',
            'reintentos_automaticos.boolean' => 'El campo reintentos automáticos debe ser verdadero o falso.',
            'max_reintentos.required' => 'El número máximo de reintentos es obligatorio.',
            'max_reintentos.integer' => 'El máximo de reintentos debe ser un número entero.',
            'max_reintentos.min' => 'El máximo de reintentos debe ser al menos 1.',
            'max_reintentos.max' => 'El máximo de reintentos no puede superar 10.',
            'configuracion_empresa_id.exists' => 'La empresa seleccionada no existe.',
        ];
    }
}
