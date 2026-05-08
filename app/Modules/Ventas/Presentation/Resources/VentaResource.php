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
        ];
    }

    
}