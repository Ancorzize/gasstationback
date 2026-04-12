<?php

namespace App\Modules\Bodegas\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BodegaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'descripcion' => $this->descripcion,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,

            'responsable_id' => $this->responsable_id,
            'responsable' => $this->responsable ? [
                'id' => $this->responsable->id,
                'name' => $this->responsable->name,
                'email' => $this->responsable->email,
            ] : null,

            'is_principal' => $this->is_principal,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}