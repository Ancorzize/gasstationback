<?php

namespace App\Modules\ConfiguracionFacturacion\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfiguracionFacturacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'configuracion_empresa_id' => $this->configuracion_empresa_id,
            'proveedor_activo' => $this->proveedor_activo,
            'ambiente' => $this->ambiente,
            'facturacion_electronica_activa' => (bool) $this->facturacion_electronica_activa,
            'reintentos_automaticos' => (bool) $this->reintentos_automaticos,
            'max_reintentos' => (int) $this->max_reintentos,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
