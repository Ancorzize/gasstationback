<?php

namespace App\Modules\ConfiguracionFacturacion\Infrastructure\Mappers;

use App\Modules\ConfiguracionFacturacion\Application\DTOs\UpdateConfiguracionFacturacionDTO;

class ConfiguracionFacturacionMapper
{
    public static function fromArrayToUpdateDTO(array $data): UpdateConfiguracionFacturacionDTO
    {
        return new UpdateConfiguracionFacturacionDTO(
            proveedor_activo: $data['proveedor_activo'] ?? 'matias',
            ambiente: $data['ambiente'] ?? 'sandbox',
            facturacion_electronica_activa: (bool) ($data['facturacion_electronica_activa'] ?? false),
            reintentos_automaticos: (bool) ($data['reintentos_automaticos'] ?? true),
            max_reintentos: (int) ($data['max_reintentos'] ?? 3),
            configuracion_empresa_id: isset($data['configuracion_empresa_id']) ? (int) $data['configuracion_empresa_id'] : null,
        );
    }
}
