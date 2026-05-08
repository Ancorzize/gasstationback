<?php

namespace App\Modules\Bombas\Application\DTOs;

class UpdateBombaDTO
{
    public function __construct(
        public int $estacion_id,
        public string $nombre,
        public string $codigo,
    ) {}
}