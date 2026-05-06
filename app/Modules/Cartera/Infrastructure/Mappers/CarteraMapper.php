<?php

namespace App\Modules\Cartera\Infrastructure\Mappers;

use App\Modules\Cartera\Application\DTOs\CreateAbonoCarteraDTO;

class CarteraMapper
{
    public static function fromArrayToCreateAbonoDTO(array $data, int $userId): CreateAbonoCarteraDTO
    {
        return new CreateAbonoCarteraDTO(
            cliente_id: (int) $data['cliente_id'],
            fecha_abono: $data['fecha_abono'],
            valor: (float) $data['valor'],
            medio_pago: $data['medio_pago'],
            observacion: $data['observacion'] ?? null,
            user_id: $userId,
        );
    }
}