<?php

namespace App\Modules\Bombas\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeBombaStatusRequest extends FormRequest
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