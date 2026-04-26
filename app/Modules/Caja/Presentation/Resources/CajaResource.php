<?php

namespace App\Modules\Caja\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CajaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha_apertura' => $this->fecha_apertura,
            'fecha_cierre' => $this->fecha_cierre,
            'monto_apertura' => $this->monto_apertura,
            'monto_cierre_sistema' => $this->monto_cierre_sistema,
            'monto_cierre_real' => $this->monto_cierre_real,
            'diferencia_cierre' => $this->diferencia_cierre,
            'estado' => $this->estado,
            'user_apertura_id' => $this->user_apertura_id,
            'tipo_caja' => $this->tipo_caja,
            'usuario_apertura' => $this->usuarioApertura ? [
                'id' => $this->usuarioApertura->id,
                'name' => $this->usuarioApertura->name,
                'email' => $this->usuarioApertura->email,
            ] : null,
            'user_cierre_id' => $this->user_cierre_id,
            'usuario_cierre' => $this->usuarioCierre ? [
                'id' => $this->usuarioCierre->id,
                'name' => $this->usuarioCierre->name,
                'email' => $this->usuarioCierre->email,
            ] : null,
            'observacion_apertura' => $this->observacion_apertura,
            'observacion_cierre' => $this->observacion_cierre,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}