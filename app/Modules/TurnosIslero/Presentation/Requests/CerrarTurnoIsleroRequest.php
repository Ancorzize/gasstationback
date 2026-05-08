<?php

namespace App\Modules\TurnosIslero\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CerrarTurnoIsleroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lecturas_finales' => ['required', 'array', 'min:1'],
            'lecturas_finales.*.manguera_id' => ['required', 'integer', 'exists:mangueras,id'],
            'lecturas_finales.*.lectura_final' => ['required', 'numeric', 'gte:0'],

            'pagos_qr' => ['nullable', 'numeric', 'gte:0'],
            'pagos_datafono' => ['nullable', 'numeric', 'gte:0'],
            'pagos_transferencia' => ['nullable', 'numeric', 'gte:0'],
            'pagos_consignacion' => ['nullable', 'numeric', 'gte:0'],
            'pagos_efectivo' => ['nullable', 'numeric', 'gte:0'],
          
            'otros_movimientos' => ['nullable', 'numeric', 'gte:0'],
            'otros_movimientos_detalle' => ['nullable', 'string'],

            'observacion_cierre' => ['nullable', 'string'],
        ];
    }
}