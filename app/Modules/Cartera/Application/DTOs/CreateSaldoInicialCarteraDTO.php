<?php

namespace App\Modules\Cartera\Application\DTOs;

class CreateSaldoInicialCarteraDTO
{
    public function __construct(
        public int $cliente_id,
        public string $fecha_documento,
        public float $valor,
        public ?string $observacion,
        public int $user_id,
    ) {}
}