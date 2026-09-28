<?php

namespace App\Modules\Facturacion\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MapeoCatalogoFacturacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proveedor' => $this->proveedor,
            'categoria' => $this->categoria,
            'codigo_interno' => $this->codigo_interno,
            'codigo_externo' => $this->codigo_externo,
            'codigo_externo_secundario' => $this->codigo_externo_secundario,
            'descripcion' => $this->descripcion,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
