<?php

namespace App\Modules\Mangueras\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MangueraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'bomba_id' => $this->bomba_id,
            'bomba' => $this->bomba ? [
                'id' => $this->bomba->id,
                'nombre' => $this->bomba->nombre,
                'codigo' => $this->bomba->codigo,
                'estacion' => $this->bomba->estacion ? [
                    'id' => $this->bomba->estacion->id,
                    'nombre' => $this->bomba->estacion->nombre,
                    'codigo' => $this->bomba->estacion->codigo,
                ] : null,
            ] : null,

            'producto_id' => $this->producto_id,
            'producto' => $this->producto ? [
                'id' => $this->producto->id,
                'codigo' => $this->producto->codigo,
                'nombre' => $this->producto->nombre,
                'marca' => $this->producto->marca?->nombre,
                'categoria' => $this->producto->categoriaProducto?->nombre,
                'unidad_medida' => $this->producto->unidadMedida?->abreviatura,
            ] : null,

            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'precio_actual' => $this->precio_actual,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}