<?php

namespace App\Modules\Bombas\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BombaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estacion_id' => $this->estacion_id,
            'estacion' => $this->estacion ? [
                'id' => $this->estacion->id,
                'nombre' => $this->estacion->nombre,
                'codigo' => $this->estacion->codigo,
            ] : null,
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}