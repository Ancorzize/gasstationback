<?php

namespace App\Modules\Compras\Application\DTOs;

class DetalleCompraDTO
{
    public function __construct(
        public  int $producto_id,
        public  float $cantidad,
        public  float $costo_unitario,
        public  int $iva,
        public  float $soldicom,
        public float $sobre_tasa,
        public  float $total,
        public  float $iva_valor,
    ) {}

    public function subtotal(): float
    {
        return $this->cantidad * $this->costo_unitario;
    }
}