<?php

namespace App\Modules\Gastos\Infrastructure\Mappers;

use App\Modules\Gastos\Application\DTOs\CreateGastoDTO;

class GastoMapper
{
    public static function fromArrayToCreateDTO(array $data, int $userId): CreateGastoDTO
    {
        return new CreateGastoDTO(
            fecha_gasto: $data['fecha_gasto'],
            proveedor_id: isset($data['proveedor_id'])
                ? (int) $data['proveedor_id']
                : null,

            categoria_gasto_id: (int) $data['categoria_gasto_id'],

            caja_id: (int) $data['caja_id'],

            tipo_caja: $data['tipo_caja'],

            valor: (float) $data['valor'],

            descripcion: $data['descripcion'],

            user_id: $userId,
        );
    }
}