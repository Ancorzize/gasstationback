<?php

namespace App\Modules\Facturacion\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrepararDocumentoSoporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'origen' => ['required', 'string', 'in:compra,gasto'],
            'origen_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'origen.required' => 'El tipo de origen es obligatorio.',
            'origen.in' => 'El tipo de origen debe ser "compra" o "gasto".',
            'origen_id.required' => 'El ID de origen es obligatorio.',
            'origen_id.integer' => 'El ID de origen debe ser un número entero.',
            'origen_id.min' => 'El ID de origen debe ser mayor o igual a 1.',
        ];
    }
}
