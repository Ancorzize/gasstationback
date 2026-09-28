<?php

namespace App\Modules\ResolucionesFacturacion\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreResolucionFacturacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'configuracion_empresa_id' => ['nullable', 'integer', 'exists:configuracion_empresa,id'],
            'tipo_documento' => ['required', 'string', 'max:50'],
            'prefijo' => ['nullable', 'string', 'max:20'],
            'numero_resolucion' => ['required', 'string', 'max:100'],
            'fecha_resolucion' => ['nullable', 'date'],
            'rango_desde' => ['nullable', 'integer', 'min:0'],
            'rango_hasta' => ['nullable', 'integer', 'min:0'],
            'consecutivo_actual' => ['required', 'integer', 'min:0'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'clave_tecnica' => ['nullable', 'string', 'max:255'],
            'proveedor' => ['nullable', 'string', 'max:50'],
            'ambiente' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_documento.required' => 'El tipo de documento es obligatorio.',
            'numero_resolucion.required' => 'El número de resolución es obligatorio.',
            'consecutivo_actual.required' => 'El consecutivo actual es obligatorio.',
        ];
    }
}
