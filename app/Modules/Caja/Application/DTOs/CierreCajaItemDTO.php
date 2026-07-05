<?php

namespace App\Modules\Caja\Application\DTOs;

class CierreCajaItemDTO
{
    public function __construct(
        public int $caja_id,
        public float $monto_real,
    ) {}
}