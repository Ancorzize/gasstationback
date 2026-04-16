<?php

namespace App\Modules\Gastos\Application\DTOs;

class CreateGastoDTO
{
    public function __construct(
        public  string $fecha_gasto,
        public  ?int $proveedor_id,
        public  int $categoria_gasto_id,
        public  string $medio_pago,
        public  float $valor,
        public  string $descripcion,
        public  int $user_id,
    ) {}
}