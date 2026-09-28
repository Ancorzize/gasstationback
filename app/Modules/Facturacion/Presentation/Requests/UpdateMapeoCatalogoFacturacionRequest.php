<?php

namespace App\Modules\Facturacion\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMapeoCatalogoFacturacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proveedor' => ['nullable', 'string', 'max:50'],
            'categoria' => ['required', 'string', 'max:50'],
            'codigo_interno' => ['required', 'string', 'max:100'],
            'codigo_externo' => ['required', 'string', 'max:100'],
            'codigo_externo_secundario' => ['nullable', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria.required' => 'La categoría es obligatoria.',
            'codigo_interno.required' => 'El código interno (ERP) es obligatorio.',
            'codigo_externo.required' => 'El código externo (Facturación) es obligatorio.',
        ];
    }
}
