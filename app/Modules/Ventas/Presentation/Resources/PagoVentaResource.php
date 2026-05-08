<?php

namespace App\Modules\Ventas\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoVentaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'caja_id' => $this->caja_id,

            'caja' => $this->caja ? [
                'id' => $this->caja->id,
                'tipo_caja' => $this->caja->tipo_caja,
            ] : null,

            'user_id' => $this->user_id,

            'usuario' => $this->usuario ? [
                'id' => $this->usuario->id,
                'name' => $this->usuario->name,
            ] : null,

            'fecha_pago' => $this->fecha_pago,

            'monto' => $this->monto,

            'metodo_pago' => $this->metodo_pago,

            'observacion' => $this->observacion,

            'created_at' => $this->created_at,
        ];
    }
}