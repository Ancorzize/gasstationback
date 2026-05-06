<?php

namespace App\Modules\Cartera\Application\DTOs;

class CreateAbonoCarteraDTO
{
    public function __construct(
        public int $cliente_id,
        public string $fecha_abono,
        public float $valor,
        public string $medio_pago,
        public ?string $observacion,
        public int $user_id,
    ) {}
}