<?php

namespace App\Modules\Ubicaciones\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartamentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pais_id' => $this->pais_id,
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'is_active' => $this->is_active,
        ];
    }
}