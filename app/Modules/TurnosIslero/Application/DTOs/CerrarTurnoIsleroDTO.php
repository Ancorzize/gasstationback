<?php

namespace App\Modules\TurnosIslero\Application\DTOs;

class CerrarTurnoIsleroDTO
{
    public function __construct(
        public int $turno_id,
        public int $user_id,

        public array $lecturas_finales,

        public float $pagos_qr,
        public float $pagos_datafono,
        public float $pagos_transferencia,
        public float $pagos_consignacion,
        public float $pagos_efectivo,
        public float $total_creditos,
        public float $total_abonos,
        public float $otros_movimientos,
        public ?string $otros_movimientos_detalle,

        public ?string $observacion_cierre,
    ) {}
}