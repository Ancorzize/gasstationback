<?php

namespace App\Modules\Clientes\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeClienteStatusRequest extends FormRequest
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