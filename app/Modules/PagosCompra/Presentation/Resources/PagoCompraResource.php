<?php

namespace App\Modules\PagosCompra\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoCompraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'compra_id' => $this->compra_id,
            'fecha_pago' => $this->fecha_pago?->format('Y-m-d'),
            'monto' => $this->monto,
            'metodo_pago' => $this->metodo_pago,
            'observacion' => $this->observacion,

            'usuario' => $this->usuario ? [
                'id' => $this->usuario->id,
                'name' => $this->usuario->name,
                'email' => $this->usuario->email,
            ] : null,

            'compra' => $this->compra ? [
                'id' => $this->compra->id,
                'numero_documento' => $this->compra->numero_documento,
                'fecha_compra' => $this->compra->fecha_compra?->format('Y-m-d'),
                'tipo_pago' => $this->compra->tipo_pago,
                'estado' => $this->compra->estado,
                'estado_pago' => $this->compra->estado_pago,
                'subtotal' => $this->compra->subtotal,
                'impuesto' => $this->compra->impuesto,
                'soldicom' => $this->compra->soldicom,
                'total' => $this->compra->total,
                'total_pagado' => $this->compra->total_pagado,
                'saldo_pendiente' => $this->compra->saldo_pendiente,
            ] : null,

            'proveedor' => $this->compra && $this->compra->proveedor ? [
                'id' => $this->compra->proveedor->id,
                'nombre' => $this->compra->proveedor->nombre,
                'nit' => $this->compra->proveedor->nit,
            ] : null,

            'bodega' => $this->compra && $this->compra->bodega ? [
                'id' => $this->compra->bodega->id,
                'nombre' => $this->compra->bodega->nombre,
                'codigo' => $this->compra->bodega->codigo,
            ] : null,
            'detalles' => $this->compra->detalles->map(function ($detalle) {
                return [
                    'id' => $detalle->id,
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $detalle->cantidad,
                    'costo_unitario' => $detalle->costo_unitario,
                    'subtotal' => $detalle->subtotal,
                    'iva' => $detalle->iva,
                    'iva_valor' => $detalle->iva_valor,
                    'soldicom' => $detalle->soldicom,
                    'total' => $detalle->total,
                    'producto' => $detalle->producto ? [
                        'id' => $detalle->producto->id,
                        'codigo' => $detalle->producto->codigo,
                        'nombre' => $detalle->producto->nombre,
                        'marca' => $detalle->producto->marca?->nombre,
                        'categoria' => $detalle->producto->categoriaProducto?->nombre,
                        'unidad_medida' => $detalle->producto->unidadMedida?->abreviatura,
                    ] : null,
                ];
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}