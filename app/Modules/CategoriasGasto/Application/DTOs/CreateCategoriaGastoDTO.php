<?php

namespace App\Modules\CategoriasGasto\Application\DTOs;

class CreateCategoriaGastoDTO
{
    public function __construct(
        public  string $nombre,
        public  ?string $descripcion,
    ) {}
}