<?php

namespace App\Modules\Mangueras\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMangueraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bomba_id' => ['required', 'integer', 'exists:bombas,id'],
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'nombre' => ['required', 'string', 'max:150'],
            'codigo' => ['required', 'string', 'max:50', 'unique:mangueras,codigo'],
        ];
    }

    public function messages(): array
    {
        return [
            'bomba_id.required' => 'La bomba es obligatoria.',
            'bomba_id.exists' => 'La bomba seleccionada no existe.',
            'producto_id.required' => 'El producto combustible es obligatorio.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'nombre.required' => 'El nombre de la manguera es obligatorio.',
            'codigo.required' => 'El código de la manguera es obligatorio.',
            'codigo.unique' => 'Ya existe una manguera con ese código.',
        ];
    }
}