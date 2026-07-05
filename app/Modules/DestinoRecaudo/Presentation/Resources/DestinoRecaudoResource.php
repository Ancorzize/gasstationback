<?php

namespace App\Modules\DestinoRecaudo\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DestinoRecaudoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'id'=>$this->id,

            'codigo'=>$this->codigo,

            'nombre'=>$this->nombre,

            'descripcion'=>$this->descripcion,

            'is_active'=>$this->is_active,

            'created_at'=>$this->created_at,

            'updated_at'=>$this->updated_at,

        ];
    }
}