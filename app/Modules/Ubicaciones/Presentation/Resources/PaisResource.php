<?php

namespace App\Modules\Ubicaciones\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'codigo_iso' => $this->codigo_iso,
            'is_active' => $this->is_active,
        ];
    }
}