<?php

namespace App\Modules\Clientes\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellidos' => $this->apellidos,
            'documento' => $this->documento,
            'telefono_uno' => $this->telefono_uno,
            'telefono_dos' => $this->telefono_dos,
            'direccion' => $this->direccion,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}