<?php

namespace App\Modules\ConfiguracionFacturacion\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfiguracionFacturacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hasToken = !empty($this->getEffectiveMatiasToken());

        return [
            'id' => $this->id,
            'configuracion_empresa_id' => $this->configuracion_empresa_id,
            'proveedor_activo' => $this->proveedor_activo,
            'ambiente' => $this->ambiente,
            'facturacion_electronica_activa' => (bool) $this->facturacion_electronica_activa,
            'facturar_ventas_pos' => (bool) $this->facturar_ventas_pos,
            'facturar_ventas_combustible' => (bool) $this->facturar_ventas_combustible,
            'permitir_ventas_sin_datos_fiscales' => (bool) $this->permitir_ventas_sin_datos_fiscales,
            'reintentos_automaticos' => (bool) $this->reintentos_automaticos,
            'max_reintentos' => (int) $this->max_reintentos,
            'matias_api_token' => $hasToken ? '********' : '',
            'matias_api_token_configured' => $hasToken,
            'matias_sandbox_url' => $this->getEffectiveMatiasSandboxUrl(),
            'matias_ds_real_transmission' => $this->getEffectiveMatiasDsRealTransmission(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
