<?php

namespace App\Modules\Productos\Application\DTOs;

class CreateProductoDTO
{
    public function __construct(
        public string $codigo,
        public string $nombre,
        public ?string $descripcion,
        public ?int $marca_id,
        public int $categoria_producto_id,
        public int $unidad_medida_id,
        public ?float $precio_compra,
        public float $precio_venta,
        public string $tipo_impuesto,
        public float $porcentaje_iva,
        public ?string $codigo_impuesto,
        public bool $permite_decimal,
        public ?string $codigo_barras,
    ) {}
}