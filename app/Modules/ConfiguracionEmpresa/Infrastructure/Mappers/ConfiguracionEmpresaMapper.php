<?php

namespace App\Modules\ConfiguracionEmpresa\Infrastructure\Mappers;

use App\Modules\ConfiguracionEmpresa\Application\DTOs\UpdateConfiguracionEmpresaDTO;

class ConfiguracionEmpresaMapper
{
    public static function fromArrayToUpdateDTO(array $data): UpdateConfiguracionEmpresaDTO
    {
        return new UpdateConfiguracionEmpresaDTO(
            nombre_empresa: $data['nombre_empresa'],
            nombre_comercial: $data['nombre_comercial'] ?? null,
            nit: $data['nit'],
            dv: $data['dv'] ?? null,
            email: $data['email'] ?? null,
            telefono: $data['telefono'] ?? null,
            direccion: $data['direccion'] ?? null,
            pais_id: $data['pais_id'] ?? null,
            departamento_id: $data['departamento_id'] ?? null,
            ciudad_id: $data['ciudad_id'] ?? null,
            logo_url: $data['logo_url'] ?? null,
            responsable_iva: (bool) $data['responsable_iva'],
            regimen: $data['regimen'] ?? null,
            porcentaje_iva: (float) $data['porcentaje_iva'],
            maneja_iva_incluido: (bool) $data['maneja_iva_incluido'],
            prefijo_factura: $data['prefijo_factura'] ?? null,
            numero_resolucion: $data['numero_resolucion'] ?? null,
            fecha_resolucion: $data['fecha_resolucion'] ?? null,
            rango_desde: $data['rango_desde'] ?? null,
            rango_hasta: $data['rango_hasta'] ?? null,
            fecha_vencimiento: $data['fecha_vencimiento'] ?? null,
            moneda: $data['moneda'],
            simbolo_moneda: $data['simbolo_moneda'],
            decimales: (int) $data['decimales'],
        );
    }
}