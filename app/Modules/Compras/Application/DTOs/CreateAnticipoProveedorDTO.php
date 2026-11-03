<?php

namespace App\Modules\Compras\Application\DTOs;

class CreateAnticipoProveedorDTO
{
    public function __construct(
        public int $proveedor_id,
        public int $user_id,
        public int $caja_id,
        public float $monto,
        public string $metodo_pago,
        public ?string $referencia = null,
        public ?string $observacion = null,
        public ?string $fecha_pago = null,
    ) {}
}
