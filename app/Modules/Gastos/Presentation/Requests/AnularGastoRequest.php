<?php

namespace App\Modules\Gastos\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnularGastoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo_anulacion' => ['required', 'string', 'min:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo_anulacion.required' => 'El motivo de anulación es obligatorio.',
            'motivo_anulacion.min' => 'El motivo de anulación debe tener al menos 5 caracteres.',
        ];
    }
}