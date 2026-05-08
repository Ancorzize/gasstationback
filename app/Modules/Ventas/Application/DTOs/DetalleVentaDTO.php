<?php

namespace App\Modules\Ventas\Application\DTOs;

class DetalleVentaDTO
{
    public function __construct(
        public int $producto_id,
        public float $cantidad,
        public float $precio_unitario,
        public float $descuento,
        public int $iva,
        public float $iva_valor,
        public float $soldicom,
        public float $sobre_tasa,
        public float $total,
    ) {}

    public function subtotal(): float
    {
        return ($this->cantidad * $this->precio_unitario) - $this->descuento;
    }
}