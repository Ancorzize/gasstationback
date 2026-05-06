<?php

namespace App\Modules\Clientes\Application\DTOs;

class UpdateCreditoClienteDTO
{
    public function __construct(
        public bool $maneja_credito,
        public float $cupo_credito,
        public ?int $dias_credito,
    ) {}
}