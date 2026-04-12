<?php

namespace App\Modules\Compras\Application\DTOs;

class UpdateCompraDTO
{
    /**
     * @param DetalleCompraDTO[] $detalles
     */
    public function __construct(
        public  int $proveedor_id,
        public  int $bodega_id,
        public  ?string $numero_documento,
        public  string $fecha_compra,
        public  ?string $fecha_vencimiento,
        public  string $tipo_pago,
        public  float $impuesto,
        public  ?string $observacion,
        public  array $detalles,
    ) {}
}