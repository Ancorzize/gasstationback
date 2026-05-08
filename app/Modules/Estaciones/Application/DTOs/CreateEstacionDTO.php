<?php

namespace App\Modules\Estaciones\Application\DTOs;

class CreateEstacionDTO
{
    public function __construct(
        public string $nombre,
        public string $codigo,
        public ?string $direccion,
    ) {}
}