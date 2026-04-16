<?php

namespace App\Modules\Caja\Infrastructure\Mappers;

use App\Modules\Caja\Application\DTOs\AperturaCajaDTO;
use App\Modules\Caja\Application\DTOs\CierreCajaDTO;

class CajaMapper
{
    public static function fromArrayToAperturaDTO(array $data, int $userId): AperturaCajaDTO
    {
        return new AperturaCajaDTO(
            monto_apertura: (float) $data['monto_apertura'],
            observacion_apertura: $data['observacion_apertura'] ?? null,
            user_id: $userId,
        );
    }

    public static function fromArrayToCierreDTO(array $data, int $userId): CierreCajaDTO
    {
        return new CierreCajaDTO(
            monto_cierre_real: (float) $data['monto_cierre_real'],
            observacion_cierre: $data['observacion_cierre'] ?? null,
            user_id: $userId,
        );
    }
}