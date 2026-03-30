<?php

namespace App\Modules\Marcas\Infrastructure\Mappers;

use App\Modules\Marcas\Application\DTOs\CreateMarcaDTO;
use App\Modules\Marcas\Application\DTOs\UpdateMarcaDTO;

class MarcaMapper
{
    public static function fromArrayToCreateDTO(array $data): CreateMarcaDTO
    {
        return new CreateMarcaDTO(
            nombre: $data['nombre'],
            descripcion: $data['descripcion'] ?? null,
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateMarcaDTO
    {
        return new UpdateMarcaDTO(
            nombre: $data['nombre'],
            descripcion: $data['descripcion'] ?? null,
        );
    }
}