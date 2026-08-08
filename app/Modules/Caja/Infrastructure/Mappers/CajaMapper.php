<?php

namespace App\Modules\Caja\Infrastructure\Mappers;

use App\Modules\Caja\Application\DTOs\AperturaCajaDTO;
use App\Modules\Caja\Application\DTOs\CierreCajaDTO;
use App\Modules\Caja\Application\DTOs\CierreCajaItemDTO;
use App\Modules\Caja\Application\DTOs\IngresoCajaDTO;
use App\Modules\Caja\Application\DTOs\RetiroCajaDTO;
use App\Modules\Caja\Application\DTOs\TransferenciaCajaDTO;
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

    public static function fromArrayToIngresoDTO(
        array $data,
        int $userId
    ): IngresoCajaDTO
    {
        return new IngresoCajaDTO(

            caja_id: (int)$data['caja_id'],

            monto: (float)$data['monto'],

            medio_pago: $data['medio_pago'],

            descripcion: $data['descripcion'] ?? null,

            user_id: $userId

        );
    }

    public static function fromArrayToRetiroDTO(
        array $data,
        int $userId
    ): RetiroCajaDTO
    {
        return new RetiroCajaDTO(

            caja_id: (int)$data['caja_id'],

            monto: (float)$data['monto'],

            medio_pago: $data['medio_pago'],

            descripcion: $data['descripcion'] ?? null,

            user_id: $userId

        );
    }

    public static function fromArrayToTransferenciaDTO(
        array $data,
        int $userId
    ): TransferenciaCajaDTO
    {
        return new TransferenciaCajaDTO(

            caja_origen_id:(int)$data['caja_origen_id'],

            caja_destino_id:(int)$data['caja_destino_id'],

            monto:(float)$data['monto'],

            descripcion:$data['descripcion'] ?? null,

            user_id:$userId

        );
    }
}