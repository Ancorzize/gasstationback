<?php
namespace App\Modules\Proveedores\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'nit' => ['required', 'string', 'max:50', 'unique:proveedores,nit'],
            'telefono' => ['nullable', 'string'],
            'direccion' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
        ]; 
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nit.required' => 'El nit es obligatorio.',
            'nit.unique' => 'El nit ya está registrado.',
        ];
    }
}