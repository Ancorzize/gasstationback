<?php

namespace App\Modules\Inventarios\Application\DTOs;

class CreateMovimientoInventarioDTO
{
    public function __construct(
        public int $producto_id,
        public int $bodega_origen_id,
        public int $bodega_destino_id,
        public float $cantidad,
        public ?string $observacion,
        public int $user_id
    ) {}
}