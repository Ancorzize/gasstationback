<?php

namespace App\Modules\Dashboard\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardWidgetRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

            'widget_id' => $this->id,

            'codigo' => $this->codigo,

            'nombre' => $this->nombre,

            'tipo' => $this->tipo,

            'visible' => (bool) ($this->visible ?? false),

            'orden' => (int) ($this->orden ?? 999),

        ];
    }
}