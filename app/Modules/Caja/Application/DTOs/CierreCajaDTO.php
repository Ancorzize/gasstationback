<?php

namespace App\Modules\Caja\Application\DTOs;

class CierreCajaDTO
{
    public function __construct(
        public float $monto_cierre_real_efectivo,
        public float $monto_cierre_real_digital,
        public ?string $observacion_cierre,
        public int $user_id,
    ) {}
}