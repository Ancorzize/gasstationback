<?php

namespace App\Modules\UnidadesMedida\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnidadMedidaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150', 'unique:unidades_medida,nombre'],
            'abreviatura' => ['required', 'string', 'max:20', 'unique:unidades_medida,abreviatura'],
            'descripcion' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'La unidad de medida ya está registrada.',
            'abreviatura.required' => 'La abreviatura es obligatoria.',
            'abreviatura.unique' => 'La abreviatura ya está registrada.',
        ];
    }
}