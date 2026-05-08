<?php

namespace App\Modules\Ventas\Application\DTOs;

class PagoVentaDTO
{
    public function __construct(
        public string $metodo_pago,
        public float $monto,
        public ?string $observacion,
    ) {}
}