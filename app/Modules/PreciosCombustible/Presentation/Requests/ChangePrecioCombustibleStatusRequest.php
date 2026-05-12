<?php

namespace App\Modules\PreciosCombustible\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePrecioCombustibleStatusRequest extends FormRequest
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
}