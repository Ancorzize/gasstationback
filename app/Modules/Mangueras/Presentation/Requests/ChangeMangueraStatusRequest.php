<?php

namespace App\Modules\Mangueras\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeMangueraStatusRequest extends FormRequest
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