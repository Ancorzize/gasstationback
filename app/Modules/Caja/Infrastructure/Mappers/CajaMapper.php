<?php

namespace App\Modules\Caja\Infrastructure\Mappers;

use App\Modules\Caja\Application\DTOs\AperturaCajaDTO;
use App\Modules\Caja\Application\DTOs\CierreCajaDTO;

class CajaMapper
{
    public static function fromArrayToAperturaDTO(array $data, int $userId): AperturaCajaDTO
    {
        return new AperturaCajaDTO(
            monto_apertura_efectivo: (float) $data['monto_apertura_efectivo'],
            monto_apertura_digital: (float) $data['monto_apertura_digital'],
            observacion_apertura: $data['observacion_apertura'] ?? null,
            user_id: $userId,
        );
    }

    public static function fromArrayToCierreDTO(array $data, int $userId): CierreCajaDTO
    {
        return new CierreCajaDTO(
            monto_cierre_real_efectivo: (float) $data['monto_cierre_real_efectivo'],
            monto_cierre_real_digital: (float) $data['monto_cierre_real_digital'],
            observacion_cierre: $data['observacion_cierre'] ?? null,
            user_id: $userId,
        );
    }
}