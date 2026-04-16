<?php

namespace App\Modules\CategoriasGasto\Application\DTOs;

class UpdateCategoriaGastoDTO
{
    public function __construct(
        public  string $nombre,
        public  ?string $descripcion,
    ) {}
}