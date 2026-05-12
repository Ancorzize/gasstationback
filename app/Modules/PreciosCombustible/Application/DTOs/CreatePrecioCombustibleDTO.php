<?php

namespace App\Modules\PreciosCombustible\Application\DTOs;

class CreatePrecioCombustibleDTO
{
    public function __construct(
        public int $producto_id,
        public float $precio,
        public ?string $fecha_inicio,
    ) {}
}