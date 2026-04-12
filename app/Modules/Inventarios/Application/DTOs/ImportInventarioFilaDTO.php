<?php

namespace App\Modules\Inventarios\Application\DTOs;

class ImportInventarioFilaDTO
{
    public function __construct(
        public  int $fila,
        public  string $codigo_producto,
        public  string $bodega_codigo,
        public  float $cantidad,
        public  ?string $observacion,
        public  int $user_id,
    ) {}
}