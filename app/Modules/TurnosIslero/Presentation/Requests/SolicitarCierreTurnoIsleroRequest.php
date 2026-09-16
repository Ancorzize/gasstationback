<?php

namespace App\Modules\TurnosIslero\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SolicitarCierreTurnoIsleroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $turnoId = $this->route('id') ?? $this->route('turno');
        $turno = $turnoId ? \App\Models\TurnoIslero::find($turnoId) : null;
        $usuarioTurno = $turno ? ($turno->usuario ?? \App\Models\User::find($turno->user_id)) : $this->user();
        $requiereCombustible = $usuarioTurno ? $usuarioTurno->can('vender_combustible') : true;

        return [

            'lecturas_finales' => $requiereCombustible
                ? ['required', 'array', 'min:1']
                : ['nullable', 'array'],

            'lecturas_finales.*.manguera_id' => [
                'required_with:lecturas_finales',
                'integer',
                'exists:mangueras,id'
            ],

            'lecturas_finales.*.lectura_final' => [
                'required_with:lecturas_finales',
                'numeric',
                'gte:0'
            ],

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

            'destinos_recaudo' => [
                'required',
                'array',
                'min:1'
            ],

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