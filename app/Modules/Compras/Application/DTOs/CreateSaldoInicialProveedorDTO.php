<?php

namespace App\Modules\Compras\Application\DTOs;

class CreateSaldoInicialProveedorDTO
{
    public function __construct(
        public int $proveedor_id,
        public ?string $numero_documento,
        public string $fecha_documento,
        public float $valor,
        public ?string $observacion,
        public int $user_id,
    ) {}
}
