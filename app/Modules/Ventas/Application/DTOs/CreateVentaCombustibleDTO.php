<?php

namespace App\Modules\Ventas\Application\DTOs;

class CreateVentaCombustibleDTO
{
    public function __construct(
        public int $manguera_id,
        public string $tipo_venta,
        public ?int $cliente_id,
        public string $metodo_pago,
        public float $monto,
        public ?string $observacion,
        public int $user_id,
    ) {}
}