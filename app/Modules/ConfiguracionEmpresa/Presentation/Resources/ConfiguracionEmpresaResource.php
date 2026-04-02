<?php

namespace App\Modules\ConfiguracionEmpresa\Presentation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfiguracionEmpresaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'nombre_empresa' => $this->nombre_empresa,
            'nombre_comercial' => $this->nombre_comercial,
            'nit' => $this->nit,
            'dv' => $this->dv,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,

            'pais_id' => $this->pais_id,
            'pais' => $this->pais ? [
                'id' => $this->pais->id,
                'nombre' => $this->pais->nombre,
                'codigo_iso' => $this->pais->codigo_iso,
            ] : null,

            'departamento_id' => $this->departamento_id,
            'departamento' => $this->departamento ? [
                'id' => $this->departamento->id,
                'nombre' => $this->departamento->nombre,
                'codigo' => $this->departamento->codigo,
            ] : null,

            'ciudad_id' => $this->ciudad_id,
            'ciudad' => $this->ciudad ? [
                'id' => $this->ciudad->id,
                'nombre' => $this->ciudad->nombre,
                'codigo' => $this->ciudad->codigo,
            ] : null,

            'logo' => $this->logo_base64 ? [
                'base64' => $this->logo_base64,
                'mime_type' => $this->logo_mime_type,
                'src' => 'data:' . $this->logo_mime_type . ';base64,' . $this->logo_base64,
            ] : null,

            'responsable_iva' => $this->responsable_iva,
            'regimen' => $this->regimen,
            'porcentaje_iva' => $this->porcentaje_iva,
            'maneja_iva_incluido' => $this->maneja_iva_incluido,

            'prefijo_factura' => $this->prefijo_factura,
            'numero_resolucion' => $this->numero_resolucion,
            'fecha_resolucion' => $this->fecha_resolucion?->format('Y-m-d'),
            'rango_desde' => $this->rango_desde,
            'rango_hasta' => $this->rango_hasta,
            'fecha_vencimiento' => $this->fecha_vencimiento?->format('Y-m-d'),

            'moneda' => $this->moneda,
            'simbolo_moneda' => $this->simbolo_moneda,
            'decimales' => $this->decimales,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}