<?php

namespace App\Modules\Facturacion\Infrastructure\Mappers;

use App\Modules\Facturacion\Application\DTOs\CreateMapeoCatalogoFacturacionDTO;
use App\Modules\Facturacion\Application\DTOs\UpdateMapeoCatalogoFacturacionDTO;

class MapeoCatalogoFacturacionMapper
{
    public static function fromArrayToCreateDTO(array $data): CreateMapeoCatalogoFacturacionDTO
    {
        return new CreateMapeoCatalogoFacturacionDTO(
            proveedor: $data['proveedor'] ?? 'matias',
            categoria: $data['categoria'],
            codigo_interno: $data['codigo_interno'],
            codigo_externo: $data['codigo_externo'],
            codigo_externo_secundario: $data['codigo_externo_secundario'] ?? null,
            descripcion: $data['descripcion'] ?? null,
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : true,
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateMapeoCatalogoFacturacionDTO
    {
        return new UpdateMapeoCatalogoFacturacionDTO(
            proveedor: $data['proveedor'] ?? 'matias',
            categoria: $data['categoria'],
            codigo_interno: $data['codigo_interno'],
            codigo_externo: $data['codigo_externo'],
            codigo_externo_secundario: $data['codigo_externo_secundario'] ?? null,
            descripcion: $data['descripcion'] ?? null,
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : true,
        );
    }
}
