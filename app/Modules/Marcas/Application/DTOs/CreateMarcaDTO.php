<?php

namespace App\Modules\Marcas\Application\DTOs;

class CreateMarcaDTO
{
    public function __construct(
        public string $nombre,
        public ?string $descripcion,
    ) {}
}