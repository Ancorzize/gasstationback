<?php

namespace App\Modules\Bodegas\Application\DTOs;

class CreateBodegaDTO
{
    public function __construct(
        public string $nombre,
        public string $codigo,
        public ?string $descripcion,
        public ?string $direccion,
        public ?string $telefono,
        public ?int $responsable_id,
        public bool $is_principal,
    ) {}
}