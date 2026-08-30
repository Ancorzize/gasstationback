<?php

namespace App\Modules\TurnosIslero\Application\DTOs;

class SolicitarCierreTurnoIsleroDTO
{
    public function __construct(
        public int $turno_id,
        public int $user_id,
        public array $lecturas_finales,
        public array $destinos_recaudo,
        public float $otros_movimientos = 0,
        public ?string $otros_movimientos_detalle = null,
        public ?string $observacion_cierre = null,
    ) {}
}