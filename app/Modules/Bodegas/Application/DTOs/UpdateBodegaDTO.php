<?php

namespace App\Modules\Bodegas\Application\DTOs;

class UpdateBodegaDTO
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