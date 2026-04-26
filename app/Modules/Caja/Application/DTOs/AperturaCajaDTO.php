<?php

namespace App\Modules\Caja\Application\DTOs;

class AperturaCajaDTO
{
    public function __construct(
        public float $monto_apertura_efectivo,
        public float $monto_apertura_digital,
        public ?string $observacion_apertura,
        public int $user_id,
    ) {}
}