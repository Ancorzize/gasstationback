<?php

namespace App\Modules\MovimientosInventario\Application\DTOs;

class MovimientoInventarioMasivoItemDTO
{
    public function __construct(
        public int $producto_id,
        public float $cantidad,
    ) {}
}