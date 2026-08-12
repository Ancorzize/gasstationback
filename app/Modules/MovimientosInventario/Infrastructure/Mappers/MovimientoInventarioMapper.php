<?php

namespace App\Modules\MovimientosInventario\Infrastructure\Mappers;

use App\Modules\MovimientosInventario\Application\DTOs\CreateMovimientoInventarioDTO;
use App\Modules\MovimientosInventario\Application\DTOs\CreateMovimientoInventarioMasivoDTO;
use App\Modules\MovimientosInventario\Application\DTOs\MovimientoInventarioMasivoItemDTO;

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

    public static function fromArrayToMasivoDTO(
        array $data,
        int $userId
    ): CreateMovimientoInventarioMasivoDTO {
        $items = array_map(
            fn ($item) => new MovimientoInventarioMasivoItemDTO(
                producto_id: (int) $item['producto_id'],
                cantidad: (float) $item['cantidad'],
            ),
            $data['items']
        );

        return new CreateMovimientoInventarioMasivoDTO(
            bodega_origen_id: (int) $data['bodega_origen_id'],
            bodega_destino_id: (int) $data['bodega_destino_id'],
            items: $items,
            observacion: $data['observacion'] ?? null,
            user_id: $userId,
        );
    }
}