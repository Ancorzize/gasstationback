<?php

namespace App\Modules\Marcas\Application\DTOs;

class UpdateMarcaDTO
{
    public function __construct(
        public string $nombre,
        public ?string $descripcion,
    ) {}
}