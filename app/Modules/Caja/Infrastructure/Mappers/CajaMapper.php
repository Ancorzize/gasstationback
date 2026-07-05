<?php

namespace App\Modules\Caja\Infrastructure\Mappers;

use App\Modules\Caja\Application\DTOs\AperturaCajaDTO;
use App\Modules\Caja\Application\DTOs\CierreCajaDTO;
use App\Modules\Caja\Application\DTOs\CierreCajaItemDTO;

class CajaMapper
{
    public static function fromArrayToAperturaDTO(
        array $data,
        int $userId
    ): AperturaCajaDTO
    {
        return new AperturaCajaDTO(
            nombre: $data['nombre'],
            tipo_caja: $data['tipo_caja'],
            destino_recaudo_id: (int) $data['destino_recaudo_id'],
            monto_apertura: (float) $data['monto_apertura'],
            observacion_apertura: $data['observacion_apertura'] ?? null,
            user_id: $userId,
        );
    }

    public static function fromArrayToCierreDTO(
        array $data,
        int $userId
    ): CierreCajaDTO
    {
        $cierres = array_map(
            fn($item) => new CierreCajaItemDTO(
                caja_id: (int) $item['caja_id'],
                monto_real: (float) $item['monto_real']
            ),
            $data['cierres']
        );

        return new CierreCajaDTO(
            cierres: $cierres,
            observacion_cierre:
                $data['observacion_cierre'] ?? null,
            user_id: $userId
        );
    }
}