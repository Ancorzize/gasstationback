<?php

namespace App\Modules\Ventas\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VentaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'prefijo' => $this->prefijo,
            'numero_factura' => $this->numero_factura,

            'cliente_id' => $this->cliente_id,

            'cliente' => $this->cliente ? [
                'id' => $this->cliente->id,
                'nombre' => $this->cliente->nombre,
                'apellidos' => $this->cliente->apellidos,
                'documento' => $this->cliente->documento,
            ] : null,

            'user_id' => $this->user_id,

            'usuario' => $this->usuario ? [
                'id' => $this->usuario->id,
                'name' => $this->usuario->name,
                'email' => $this->usuario->email,
            ] : null,

            'tipo_venta' => $this->tipo_venta,
            'estado' => $this->estado,
            'estado_pago' => $this->estado_pago,

            'subtotal' => $this->subtotal,
            'descuento' => $this->descuento,
            'impuesto' => $this->impuesto,
            'soldicom' => $this->soldicom,
            'sobre_tasa' => $this->sobre_tasa,
            'total' => $this->total,

            'total_pagado' => $this->total_pagado,
            'saldo_pendiente' => $this->saldo_pendiente,

            'fecha_venta' => $this->fecha_venta,

            'observacion' => $this->observacion,

            'detalles' => DetalleVentaResource::collection(
                $this->whenLoaded('detalles')
            ),

            'pagos' => PagoVentaResource::collection(
                $this->whenLoaded('pagos')
            ),
            'tipo_origen' => $this->tipo_origen,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'motivo_anulacion' => $this->motivo_anulacion,
            'user_anulacion_id' => $this->user_anulacion_id,
            'usuario_anulacion' => $this->usuarioAnulacion ? [
                'id' => $this->usuarioAnulacion->id,
                'name' => $this->usuarioAnulacion->name,
                'email' => $this->usuarioAnulacion->email,
            ] : null,
            'fecha_anulacion' => $this->fecha_anulacion,
            'turno_islero_id' => $this->turno_islero_id,
            'turno_islero' => $this->turnoIslero ? [
                'id' => $this->turnoIslero->id,
                'estado' => $this->turnoIslero->estado,
                'fecha_apertura' => $this->turnoIslero->fecha_apertura,
                'fecha_cierre' => $this->turnoIslero->fecha_cierre,
                'estacion' => $this->turnoIslero->estacion ? [
                    'id' => $this->turnoIslero->estacion->id,
                    'nombre' => $this->turnoIslero->estacion->nombre,
                    'codigo' => $this->turnoIslero->estacion->codigo,
                ] : null,
            ] : null,
            'abonos_cartera' => $this->whenLoaded(
                'abonosCartera',
                    function () {
                        return $this->abonosCartera->map(function ($detalle) {
                            return [
                                'id' => $detalle->id,
                                'valor_aplicado' => $detalle->valor_aplicado,
                                'abono_cartera_id' => $detalle->abono_cartera_id,
                                'fecha_abono' => $detalle->abonoCartera?->fecha_abono,
                                'medio_pago' => $detalle->abonoCartera?->medio_pago,
                                'observacion' => $detalle->abonoCartera?->observacion,
                                'usuario' => $detalle->abonoCartera?->usuario
                                    ? [
                                        'id' => $detalle->abonoCartera->usuario->id,
                                        'name' => $detalle->abonoCartera->usuario->name,
                                    ]
                                    : null,
                            ];
                        });
                    }
                ),
            ];
    }

    
}