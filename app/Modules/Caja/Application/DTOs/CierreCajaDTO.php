<?php

namespace App\Modules\Caja\Application\DTOs;

class CierreCajaDTO
{
    public function __construct(
        public  float $monto_cierre_real,
        public  ?string $observacion_cierre,
        public  int $user_id,
    ) {}
}