<?php

namespace App\Modules\UnidadesMedida\Application\DTOs;

class CreateUnidadMedidaDTO
{
    public function __construct(
        public string $nombre,
        public string $abreviatura,
        public ?string $descripcion,
    ) {}
}