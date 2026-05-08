<?php

namespace App\Modules\Estaciones\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeEstacionStatusRequest extends FormRequest
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