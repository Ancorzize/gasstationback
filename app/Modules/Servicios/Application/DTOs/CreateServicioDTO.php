<?php

namespace App\Modules\Servicios\Application\DTOs;

class CreateServicioDTO
{
    public function __construct(
        public string $codigo,
        public string $nombre,
        public ?string $descripcion,
        public float $precio,
        public ?int $unidad_medida_id,
        public bool $permite_decimal,
        public ?int $duracion_minutos,
    ) {}
}