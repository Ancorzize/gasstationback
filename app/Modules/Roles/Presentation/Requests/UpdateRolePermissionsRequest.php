<?php

namespace App\Modules\Roles\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.required' => 'La lista de permisos es obligatoria.',
            'permissions.array' => 'Los permisos deben enviarse en un arreglo.',
            'permissions.*.exists' => 'Uno de los permisos enviados no existe.',
        ];
    }
}