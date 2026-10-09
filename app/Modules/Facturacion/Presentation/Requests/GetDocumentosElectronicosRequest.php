<?php

namespace App\Modules\Facturacion\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetDocumentosElectronicosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_inicial' => ['nullable', 'date', 'date_format:Y-m-d'],
            'fecha_final' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:fecha_inicial'],
            'venta_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'max:30'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_inicial.date_format' => 'La fecha inicial debe tener el formato YYYY-MM-DD.',
            'fecha_final.date_format' => 'La fecha final debe tener el formato YYYY-MM-DD.',
            'fecha_final.after_or_equal' => 'La fecha final debe ser posterior o igual a la fecha inicial.',
            'venta_id.integer' => 'El ID de la venta debe ser un número entero.',
        ];
    }
}
