<?php

namespace App\Modules\UnidadesMedida\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnidadMedidaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'nombre' => ['required', 'string', 'max:150', 'unique:unidades_medida,nombre,' . $id],
            'abreviatura' => ['required', 'string', 'max:20', 'unique:unidades_medida,abreviatura,' . $id],
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