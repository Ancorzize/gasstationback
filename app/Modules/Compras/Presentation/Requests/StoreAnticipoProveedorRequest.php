<?php

namespace App\Modules\Compras\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnticipoProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proveedor_id' => [
                'required',
                'exists:proveedores,id',
            ],
            'caja_id' => [
                'required',
                'exists:cajas,id',
            ],
            'monto' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'metodo_pago' => [
                'required',
                'in:efectivo,transferencia,consignacion',
            ],
            'referencia' => [
                'nullable',
                'string',
                'max:100',
            ],
            'observacion' => [
                'nullable',
                'string',
            ],
            'fecha_pago' => [
                'nullable',
                'date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'proveedor_id.required' => 'Debe seleccionar un proveedor.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.',
            'caja_id.required' => 'Debe seleccionar una caja.',
            'caja_id.exists' => 'La caja seleccionada no existe.',
            'monto.required' => 'El monto del anticipo es obligatorio.',
            'monto.numeric' => 'El monto del anticipo debe ser un valor numérico.',
            'monto.gt' => 'El monto del anticipo debe ser mayor a cero.',
            'metodo_pago.required' => 'El método de pago es obligatorio.',
            'metodo_pago.in' => 'El método de pago no es válido.',
        ];
    }
}
