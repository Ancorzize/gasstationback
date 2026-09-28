<?php

namespace App\Modules\ResolucionesFacturacion\Infrastructure\Mappers;

use App\Modules\ResolucionesFacturacion\Application\DTOs\CreateResolucionFacturacionDTO;
use App\Modules\ResolucionesFacturacion\Application\DTOs\UpdateResolucionFacturacionDTO;

class ResolucionFacturacionMapper
{
    public static function fromArrayToCreateDTO(array $data): CreateResolucionFacturacionDTO
    {
        return new CreateResolucionFacturacionDTO(
            tipoDocumento: $data['tipo_documento'] ?? 'factura',
            numeroResolucion: $data['numero_resolucion'] ?? '',
            prefijo: $data['prefijo'] ?? null,
            configuracionEmpresaId: isset($data['configuracion_empresa_id']) ? (int) $data['configuracion_empresa_id'] : null,
            fechaResolucion: $data['fecha_resolucion'] ?? null,
            rangoDesde: isset($data['rango_desde']) ? (int) $data['rango_desde'] : null,
            rangoHasta: isset($data['rango_hasta']) ? (int) $data['rango_hasta'] : null,
            consecutivoActual: isset($data['consecutivo_actual']) ? (int) $data['consecutivo_actual'] : 1,
            fechaVencimiento: $data['fecha_vencimiento'] ?? null,
            claveTecnica: $data['clave_tecnica'] ?? null,
            proveedor: $data['proveedor'] ?? null,
            ambiente: $data['ambiente'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : true,
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateResolucionFacturacionDTO
    {
        return new UpdateResolucionFacturacionDTO(
            tipoDocumento: $data['tipo_documento'] ?? 'factura',
            numeroResolucion: $data['numero_resolucion'] ?? '',
            prefijo: $data['prefijo'] ?? null,
            configuracionEmpresaId: isset($data['configuracion_empresa_id']) ? (int) $data['configuracion_empresa_id'] : null,
            fechaResolucion: $data['fecha_resolucion'] ?? null,
            rangoDesde: isset($data['rango_desde']) ? (int) $data['rango_desde'] : null,
            rangoHasta: isset($data['rango_hasta']) ? (int) $data['rango_hasta'] : null,
            consecutivoActual: isset($data['consecutivo_actual']) ? (int) $data['consecutivo_actual'] : 1,
            fechaVencimiento: $data['fecha_vencimiento'] ?? null,
            claveTecnica: $data['clave_tecnica'] ?? null,
            proveedor: $data['proveedor'] ?? null,
            ambiente: $data['ambiente'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : true,
        );
    }
}
