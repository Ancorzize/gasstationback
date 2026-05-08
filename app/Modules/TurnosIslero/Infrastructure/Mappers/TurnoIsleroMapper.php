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
            observacion_apertura: $data['observacion_apertura'] ?? null,
            lecturas_iniciales: $data['lecturas_iniciales'] ?? [],
        );
    }

    public static function fromArrayToCerrarDTO(array $data, int $turnoId, int $userId): CerrarTurnoIsleroDTO
    {
        return new CerrarTurnoIsleroDTO(
            turno_id: $turnoId,
            user_id: $userId,

            lecturas_finales: $data['lecturas_finales'],

            pagos_qr: isset($data['pagos_qr']) ? (float) $data['pagos_qr'] : 0,
            pagos_datafono: isset($data['pagos_datafono']) ? (float) $data['pagos_datafono'] : 0,
            pagos_transferencia: isset($data['pagos_transferencia']) ? (float) $data['pagos_transferencia'] : 0,
            pagos_consignacion: isset($data['pagos_consignacion']) ? (float) $data['pagos_consignacion'] : 0,
            pagos_efectivo: isset($data['pagos_efectivo']) ? (float) $data['pagos_efectivo'] : 0,
            otros_movimientos: isset($data['otros_movimientos']) ? (float) $data['otros_movimientos'] : 0,
            otros_movimientos_detalle: $data['otros_movimientos_detalle'] ?? null,

            observacion_cierre: $data['observacion_cierre'] ?? null,
        );
    }
}