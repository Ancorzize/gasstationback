<?php

namespace App\Modules\Cartera\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaldoInicialCarteraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => [
                'required',
                'integer',
                'exists:clientes,id',
            ],

            'fecha_documento' => [
                'required',
                'date',
            ],

            'valor' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'observacion' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' =>
                'El cliente es obligatorio.',

            'cliente_id.exists' =>
                'El cliente seleccionado no existe.',

            'fecha_documento.required' =>
                'La fecha del saldo inicial es obligatoria.',

            'fecha_documento.date' =>
                'La fecha del saldo inicial no es válida.',

            'valor.required' =>
                'El valor del saldo inicial es obligatorio.',

            'valor.numeric' =>
                'El valor del saldo inicial debe ser numérico.',

            'valor.min' =>
                'El valor del saldo inicial debe ser mayor a cero.',
        ];
    }
}