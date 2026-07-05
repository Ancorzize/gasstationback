<?php

namespace App\Modules\DestinoRecaudo\Application\DTOs;

class UpdateDestinoRecaudoDTO
{
    public function __construct(
        public string $codigo,
        public string $nombre,
        public ?string $descripcion,
    ) {}
}