<?php

namespace App\Modules\Compras\Application\DTOs;

class CreatePagoCompraDTO
{
    public function __construct(
        public  int $compra_id,
        public  int $user_id,
        public  string $fecha_pago,
        public  float $monto,
        public  string $metodo_pago,
        public  ?string $observacion,
    ) {}
}