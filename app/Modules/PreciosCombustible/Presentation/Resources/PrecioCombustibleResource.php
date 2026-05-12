<?php

namespace App\Modules\PreciosCombustible\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrecioCombustibleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'producto_id' => $this->producto_id,
            'producto' => $this->producto ? [
                'id' => $this->producto->id,
                'codigo' => $this->producto->codigo,
                'nombre' => $this->producto->nombre,
                'marca' => $this->producto->marca?->nombre,
                'categoria' => $this->producto->categoriaProducto?->nombre,
                'unidad_medida' => $this->producto->unidadMedida?->abreviatura,
            ] : null,

            'precio' => $this->precio,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'is_active' => $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}