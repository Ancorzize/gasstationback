<?php

namespace App\Modules\Facturacion\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentoElectronicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cliente = $this->venta?->cliente;
        $clienteNombre = $cliente
            ? trim(($cliente->nombre ?? '') . ' ' . ($cliente->apellidos ?? ''))
            : null;

        $montoTotal = null;
        if ($this->datos_tecnicos && is_array($this->datos_tecnicos)) {
            if (isset($this->datos_tecnicos['payload']['legal_monetary_totals']['payable_amount'])) {
                $montoTotal = (float) $this->datos_tecnicos['payload']['legal_monetary_totals']['payable_amount'];
            } elseif (isset($this->datos_tecnicos['solicitud']['totales']['payable_amount'])) {
                $montoTotal = (float) $this->datos_tecnicos['solicitud']['totales']['payable_amount'];
            }
        }
        if ($montoTotal === null && $this->venta?->total !== null) {
            $montoTotal = (float) $this->venta->total;
        }

        return [
            'id' => $this->id,
            'venta_id' => $this->venta_id,
            'configuracion_facturacion_id' => $this->configuracion_facturacion_id,
            'tipo_documento' => $this->tipo_documento,
            'estado' => $this->estado,
            'proveedor' => $this->proveedor,
            'ambiente' => $this->ambiente,
            'identificador_externo' => $this->identificador_externo,
            'cufe' => $this->cufe,
            'numero_documento' => $this->numero_documento,
            'prefijo' => $this->prefijo,
            'track_id' => $this->track_id,
            'mensaje' => $this->mensaje,
            'errores' => $this->errores,
            'datos_tecnicos' => $this->datos_tecnicos,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Datos provenientes de relaciones y cálculos
            'monto_total' => $montoTotal,
            'venta_numero_factura' => $this->venta?->numero_factura,
            'venta_total' => $this->venta?->total !== null ? (float) $this->venta->total : null,
            'cliente_nombre' => $clienteNombre,
            'cliente_documento' => $cliente?->documento,
        ];
    }
}
