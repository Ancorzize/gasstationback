<?php

namespace App\Modules\Caja\Application\DTOs;

class RetiroCajaDTO
{
    public function __construct(
        public int $caja_id,
        public float $monto,
        public string $medio_pago,
        public ?string $descripcion,
        public int $user_id
    ) {}
}