<?php

namespace App\Modules\Compras\Application\DTOs;

class ConfirmarCompraDTO
{
    public function __construct(
        public int $caja_id,
    ) {}
}