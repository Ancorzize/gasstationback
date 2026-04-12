<?php

namespace App\Modules\Compras\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoCompraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'compra_id' => $this->compra_id,
            'user_id' => $this->user_id,
            'usuario' => $this->usuario ? [
                'id' => $this->usuario->id,
                'name' => $this->usuario->name,
                'email' => $this->usuario->email,
            ] : null,
            'fecha_pago' => $this->fecha_pago?->format('Y-m-d'),
            'monto' => $this->monto,
            'metodo_pago' => $this->metodo_pago,
            'observacion' => $this->observacion,
            'created_at' => $this->created_at,
        ];
    }
}