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
            facturar_ventas_pos: (bool) ($data['facturar_ventas_pos'] ?? true),
            facturar_ventas_combustible: (bool) ($data['facturar_ventas_combustible'] ?? true),
            permitir_ventas_sin_datos_fiscales: (bool) ($data['permitir_ventas_sin_datos_fiscales'] ?? true),
            matias_api_token: isset($data['matias_api_token']) ? (string) $data['matias_api_token'] : null,
            matias_sandbox_url: isset($data['matias_sandbox_url']) ? (string) $data['matias_sandbox_url'] : null,
            matias_ds_real_transmission: isset($data['matias_ds_real_transmission']) ? (bool) $data['matias_ds_real_transmission'] : null,
        );
    }
}
