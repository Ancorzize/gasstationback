<?php

namespace App\Modules\Mangueras\Application\DTOs;

class CreateMangueraDTO
{
    public function __construct(
        public int $bomba_id,
        public int $producto_id,
        public string $nombre,
        public string $codigo,
    ) {}
}