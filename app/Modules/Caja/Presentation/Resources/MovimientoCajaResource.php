<?php

namespace App\Modules\Caja\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovimientoCajaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'caja_id' => $this->caja_id,
            'tipo_movimiento' => $this->tipo_movimiento,
            'categoria_movimiento' => $this->categoria_movimiento,
            'origen_modulo' => $this->origen_modulo,
            'origen_id' => $this->origen_id,
            'medio_pago' => $this->medio_pago,
            'monto' => $this->monto,
            'descripcion' => $this->descripcion,
            'user_id' => $this->user_id,
            'usuario' => $this->usuario ? [
                'id' => $this->usuario->id,
                'name' => $this->usuario->name,
                'email' => $this->usuario->email,
            ] : null,
            'fecha_movimiento' => $this->fecha_movimiento,
            'created_at' => $this->created_at,
        ];
    }
}