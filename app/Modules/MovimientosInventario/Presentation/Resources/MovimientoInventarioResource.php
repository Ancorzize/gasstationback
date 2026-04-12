<?php

namespace App\Modules\MovimientosInventario\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovimientoInventarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo_movimiento' => $this->tipo_movimiento,

            'producto_id' => $this->producto_id,
            'producto' => $this->producto ? [
                'id' => $this->producto->id,
                'codigo' => $this->producto->codigo,
                'nombre' => $this->producto->nombre,
            ] : null,

            'bodega_origen_id' => $this->bodega_origen_id,
            'bodega_origen' => $this->bodegaOrigen ? [
                'id' => $this->bodegaOrigen->id,
                'nombre' => $this->bodegaOrigen->nombre,
                'codigo' => $this->bodegaOrigen->codigo,
            ] : null,

            'bodega_destino_id' => $this->bodega_destino_id,
            'bodega_destino' => $this->bodegaDestino ? [
                'id' => $this->bodegaDestino->id,
                'nombre' => $this->bodegaDestino->nombre,
                'codigo' => $this->bodegaDestino->codigo,
            ] : null,

            'cantidad' => $this->cantidad,
            'observacion' => $this->observacion,

            'user_id' => $this->user_id,
            'usuario' => $this->usuario ? [
                'id' => $this->usuario->id,
                'name' => $this->usuario->name,
                'email' => $this->usuario->email,
            ] : null,

            'created_at' => $this->created_at,
        ];
    }
}