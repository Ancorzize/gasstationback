<?php

namespace App\Modules\Uploads\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.required' => 'El archivo del logo es obligatorio.',
            'logo.image' => 'El archivo debe ser una imagen válida.',
            'logo.mimes' => 'El logo debe ser de tipo jpg, jpeg, png o webp.',
            'logo.max' => 'El logo no debe superar los 2 MB.',
        ];
    }
}