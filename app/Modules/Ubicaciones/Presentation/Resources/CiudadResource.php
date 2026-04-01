<?php

namespace App\Modules\Ubicaciones\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CiudadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'departamento_id' => $this->departamento_id,
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'is_active' => $this->is_active,
        ];
    }
}