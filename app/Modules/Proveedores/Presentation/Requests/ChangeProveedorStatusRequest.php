<?php
namespace App\Modules\Proveedores\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeProveedorStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }
}