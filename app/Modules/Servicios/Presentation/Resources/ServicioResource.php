<?php

namespace App\Modules\Servicios\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio' => $this->precio,

            'unidad_medida_id' => $this->unidad_medida_id,
            'unidad_medida' => $this->unidadMedida ? [
                'id' => $this->unidadMedida->id,
                'nombre' => $this->unidadMedida->nombre,
                'abreviatura' => $this->unidadMedida->abreviatura,
            ] : null,

            'permite_decimal' => $this->permite_decimal,
            'duracion_minutos' => $this->duracion_minutos,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}