<?php

namespace App\Modules\TurnosIslero\Infrastructure\Mappers;

use App\Modules\TurnosIslero\Application\DTOs\AbrirTurnoIsleroDTO;
use App\Modules\TurnosIslero\Application\DTOs\CerrarTurnoIsleroDTO;

class TurnoIsleroMapper
{
    public static function fromArrayToAbrirDTO(array $data, int $userId): AbrirTurnoIsleroDTO
    {
        return new AbrirTurnoIsleroDTO(
            estacion_id: (int) $data['estacion_id'],
            user_id: $userId,
            mangueras: $data['mangueras'],
            observacion_apertura: $data['observacion_apertura'] ?? null,
            lecturas_iniciales: $data['lecturas_iniciales'] ?? [],
        );
    }

    public static function fromArrayToCerrarDTO(
        array $data,
        int $turnoId,
        int $userId
    ): CerrarTurnoIsleroDTO {

        return new CerrarTurnoIsleroDTO(

            turno_id: $turnoId,

            user_id: $userId,

            lecturas_finales: $data['lecturas_finales'],

            destinos_recaudo: $data['destinos_recaudo'],

            otros_movimientos: isset($data['otros_movimientos'])
                ? (float)$data['otros_movimientos']
                : 0,

            otros_movimientos_detalle: $data['otros_movimientos_detalle'] ?? null,

            observacion_cierre: $data['observacion_cierre'] ?? null,
        );
    }
}