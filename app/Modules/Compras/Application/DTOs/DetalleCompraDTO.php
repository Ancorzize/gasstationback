<?php

namespace App\Modules\Compras\Application\DTOs;

class DetalleCompraDTO
{
    public function __construct(
        public  int $producto_id,
        public  float $cantidad,
        public  float $costo_unitario,
    ) {}

    public function subtotal(): float
    {
        return $this->cantidad * $this->costo_unitario;
    }
}