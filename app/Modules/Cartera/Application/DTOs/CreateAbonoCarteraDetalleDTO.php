<?php

namespace App\Modules\Cartera\Application\DTOs;

class CreateAbonoCarteraDetalleDTO
{
    public function __construct(

        public int $abono_cartera_id,

        public int $venta_id,

        public float $valor_aplicado,

    ) {}
}