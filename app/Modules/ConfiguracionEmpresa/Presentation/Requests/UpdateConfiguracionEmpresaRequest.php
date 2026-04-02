<?php

namespace App\Modules\ConfiguracionEmpresa\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfiguracionEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_empresa' => ['required', 'string', 'max:150'],
            'nombre_comercial' => ['nullable', 'string', 'max:150'],
            'nit' => ['required', 'string', 'max:30'],
            'dv' => ['nullable', 'string', 'max:5'],
            'email' => ['nullable', 'email', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:200'],

            'pais_id' => ['nullable', 'integer', 'exists:paises,id'],
            'departamento_id' => ['nullable', 'integer', 'exists:departamentos,id'],
            'ciudad_id' => ['nullable', 'integer', 'exists:ciudades,id'],

            'logo_url' => ['nullable', 'string', 'max:255'],

            'responsable_iva' => ['required', 'boolean'],
            'regimen' => ['nullable', 'string', 'max:100'],
            'porcentaje_iva' => ['required', 'numeric', 'min:0'],
            'maneja_iva_incluido' => ['required', 'boolean'],

            'prefijo_factura' => ['nullable', 'string', 'max:20'],
            'numero_resolucion' => ['nullable', 'string', 'max:100'],
            'fecha_resolucion' => ['nullable', 'date'],
            'rango_desde' => ['nullable', 'integer', 'min:1'],
            'rango_hasta' => ['nullable', 'integer', 'min:1'],
            'fecha_vencimiento' => ['nullable', 'date'],

            'moneda' => ['required', 'string', 'max:10'],
            'simbolo_moneda' => ['required', 'string', 'max:10'],
            'decimales' => ['required', 'integer', 'min:0', 'max:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_empresa.required' => 'El nombre de la empresa es obligatorio.',
            'nit.required' => 'El NIT es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'pais_id.exists' => 'El país seleccionado no existe.',
            'departamento_id.exists' => 'El departamento seleccionado no existe.',
            'ciudad_id.exists' => 'La ciudad seleccionada no existe.',
            'responsable_iva.required' => 'Debe indicar si la empresa es responsable de IVA.',
            'responsable_iva.boolean' => 'El campo responsable_iva debe ser verdadero o falso.',
            'porcentaje_iva.required' => 'El porcentaje de IVA es obligatorio.',
            'porcentaje_iva.numeric' => 'El porcentaje de IVA debe ser numérico.',
            'maneja_iva_incluido.required' => 'Debe indicar si el precio maneja IVA incluido.',
            'maneja_iva_incluido.boolean' => 'El campo maneja_iva_incluido debe ser verdadero o falso.',
            'rango_desde.integer' => 'El rango desde debe ser un número entero.',
            'rango_hasta.integer' => 'El rango hasta debe ser un número entero.',
            'fecha_resolucion.date' => 'La fecha de resolución no es válida.',
            'fecha_vencimiento.date' => 'La fecha de vencimiento no es válida.',
            'decimales.integer' => 'Los decimales deben ser un número entero.',
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}