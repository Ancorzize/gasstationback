<?php

namespace App\Modules\Servicios\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeServicioStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_active.required' => 'El estado es obligatorio.',
            'is_active.boolean' => 'El estado debe ser verdadero o falso.',
        ];
    }
}