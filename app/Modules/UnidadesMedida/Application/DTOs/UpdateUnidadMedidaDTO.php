<?php

namespace App\Modules\UnidadesMedida\Application\DTOs;

class UpdateUnidadMedidaDTO
{
    public function __construct(
        public string $nombre,
        public string $abreviatura,
        public ?string $descripcion,
    ) {}
}