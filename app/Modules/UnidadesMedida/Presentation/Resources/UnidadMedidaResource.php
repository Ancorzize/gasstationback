<?php

namespace App\Modules\UnidadesMedida\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnidadMedidaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'abreviatura' => $this->abreviatura,
            'descripcion' => $this->descripcion,
            'codigo_dian' => $this->codigo_dian,
            'simbolo_dian' => $this->simbolo_dian,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}