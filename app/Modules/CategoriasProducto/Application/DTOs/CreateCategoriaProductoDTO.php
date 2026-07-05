<?php

namespace App\Modules\CategoriasProducto\Application\DTOs;

class CreateCategoriaProductoDTO
{
    public function __construct(
        public string $nombre,
        public ?string $descripcion,
        public int $destino_recaudo_id
    ) {}
}