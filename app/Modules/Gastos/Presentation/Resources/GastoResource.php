<?php

namespace App\Modules\Gastos\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GastoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha_gasto' => $this->fecha_gasto?->format('Y-m-d'),

            'proveedor_id' => $this->proveedor_id,
            'proveedor' => $this->proveedor ? [
                'id' => $this->proveedor->id,
                'nombre' => $this->proveedor->nombre,
                'nit' => $this->proveedor->nit,
            ] : null,

            'categoria_gasto_id' => $this->categoria_gasto_id,
            'categoria_gasto' => $this->categoriaGasto ? [
                'id' => $this->categoriaGasto->id,
                'nombre' => $this->categoriaGasto->nombre,
            ] : null,

            'caja_id' => $this->caja_id,
            'caja' => $this->caja ? [
                'id' => $this->caja->id,
                'estado' => $this->caja->estado,
                'fecha_apertura' => $this->caja->fecha_apertura,
            ] : null,

            'user_id' => $this->user_id,
            'usuario' => $this->usuario ? [
                'id' => $this->usuario->id,
                'name' => $this->usuario->name,
                'email' => $this->usuario->email,
            ] : null,

            'medio_pago' => $this->medio_pago,
            'valor' => $this->valor,
            'descripcion' => $this->descripcion,
            'estado' => $this->estado,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}