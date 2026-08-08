<?php

namespace App\Modules\Caja\Application\DTOs;

class TransferenciaCajaDTO
{
    public function __construct(

        public int $caja_origen_id,

        public int $caja_destino_id,

        public float $monto,

        public ?string $descripcion,

        public int $user_id

    ) {}
}