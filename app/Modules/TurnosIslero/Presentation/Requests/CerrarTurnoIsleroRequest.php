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

            'lecturas_finales' => ['required','array','min:1'],

            'lecturas_finales.*.manguera_id' => [
                'required',
                'integer',
                'exists:mangueras,id'
            ],

            'lecturas_finales.*.lectura_final' => [
                'required',
                'numeric',
                'gte:0'
            ],

            /*
             |--------------------------------------------------------------------------
             | Otros movimientos
             |--------------------------------------------------------------------------
             */

            'otros_movimientos' => [
                'nullable',
                'numeric',
                'gte:0'
            ],

            'otros_movimientos_detalle' => [
                'nullable',
                'string'
            ],

            'observacion_cierre' => [
                'nullable',
                'string'
            ],
            'destinos_recaudo' => ['required','array','min:1'],

            'destinos_recaudo.*.destino_recaudo_id' => [
                'required',
                'integer',
                'exists:destinos_recaudo,id'
            ],

            'destinos_recaudo.*.pagos' => [
                'required',
                'array'
            ],

            'destinos_recaudo.*.pagos.efectivo' => [
                'nullable',
                'numeric',
                'gte:0'
            ],

            'destinos_recaudo.*.pagos.qr' => [
                'nullable',
                'numeric',
                'gte:0'
            ],

            'destinos_recaudo.*.pagos.datafono' => [
                'nullable',
                'numeric',
                'gte:0'
            ],

            'destinos_recaudo.*.pagos.transferencia' => [
                'nullable',
                'numeric',
                'gte:0'
            ],

            'destinos_recaudo.*.pagos.consignacion' => [
                'nullable',
                'numeric',
                'gte:0'
            ],
        ];
    }
}