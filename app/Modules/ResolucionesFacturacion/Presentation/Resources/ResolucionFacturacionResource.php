<?php

namespace App\Modules\ResolucionesFacturacion\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResolucionFacturacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'configuracion_empresa_id' => $this->configuracion_empresa_id,
            'tipo_documento' => $this->tipo_documento,
            'prefijo' => $this->prefijo,
            'numero_resolucion' => $this->numero_resolucion,
            'fecha_resolucion' => $this->fecha_resolucion ? $this->fecha_resolucion->format('Y-m-d') : null,
            'rango_desde' => $this->rango_desde !== null ? (int) $this->rango_desde : null,
            'rango_hasta' => $this->rango_hasta !== null ? (int) $this->rango_hasta : null,
            'consecutivo_actual' => (int) $this->consecutivo_actual,
            'fecha_vencimiento' => $this->fecha_vencimiento ? $this->fecha_vencimiento->format('Y-m-d') : null,
            'clave_tecnica' => $this->clave_tecnica,
            'proveedor' => $this->proveedor,
            'ambiente' => $this->ambiente,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
