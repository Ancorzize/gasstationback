<?php

namespace App\Modules\DestinoRecaudo\Application\DTOs;

class CreateDestinoRecaudoDTO
{
    public function __construct(
        public string $codigo,
        public string $nombre,
        public ?string $descripcion,
    ) {}
}