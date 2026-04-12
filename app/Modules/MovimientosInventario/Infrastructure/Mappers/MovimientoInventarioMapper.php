<?php

namespace App\Modules\MovimientosInventario\Infrastructure\Mappers;

use App\Modules\MovimientosInventario\Application\DTOs\CreateMovimientoInventarioDTO;

class MovimientoInventarioMapper
{
    public static function fromArrayToCreateDTO(array $data, int $userId): CreateMovimientoInventarioDTO
    {
        return new CreateMovimientoInventarioDTO(
            producto_id: (int) $data['producto_id'],
            bodega_origen_id: (int) $data['bodega_origen_id'],
            bodega_destino_id: (int) $data['bodega_destino_id'],
            cantidad: (float) $data['cantidad'],
            observacion: $data['observacion'] ?? null,
            user_id: $userId
        );
    }
}