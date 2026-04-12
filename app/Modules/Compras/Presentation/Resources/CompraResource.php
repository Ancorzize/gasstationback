<?php

namespace App\Modules\Compras\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'proveedor_id' => $this->proveedor_id,
            'proveedor' => $this->proveedor ? [
                'id' => $this->proveedor->id,
                'nombre' => $this->proveedor->nombre,
                'nit' => $this->proveedor->nit,
            ] : null,

            'bodega_id' => $this->bodega_id,
            'bodega' => $this->bodega ? [
                'id' => $this->bodega->id,
                'nombre' => $this->bodega->nombre,
                'codigo' => $this->bodega->codigo,
            ] : null,

            'user_id' => $this->user_id,
            'usuario' => $this->usuario ? [
                'id' => $this->usuario->id,
                'name' => $this->usuario->name,
                'email' => $this->usuario->email,
            ] : null,

            'numero_documento' => $this->numero_documento,
            'fecha_compra' => $this->fecha_compra?->format('Y-m-d'),
            'fecha_vencimiento' => $this->fecha_vencimiento?->format('Y-m-d'),

            'tipo_pago' => $this->tipo_pago,
            'estado' => $this->estado,
            'estado_pago' => $this->estado_pago,

            'subtotal' => $this->subtotal,
            'impuesto' => $this->impuesto,
            'total' => $this->total,
            'total_pagado' => $this->total_pagado,
            'saldo_pendiente' => $this->saldo_pendiente,

            'observacion' => $this->observacion,

            'detalles' => DetalleCompraResource::collection($this->whenLoaded('detalles')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}