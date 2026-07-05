<?php

namespace App\Modules\CategoriasProducto\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoriaProductoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'destino_recaudo_id' => $this->destino_recaudo_id
        ];
    }
}