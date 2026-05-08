<?php

namespace App\Modules\Estaciones\Infrastructure\Mappers;

use App\Modules\Estaciones\Application\DTOs\CreateEstacionDTO;
use App\Modules\Estaciones\Application\DTOs\UpdateEstacionDTO;

class EstacionMapper
{
    public static function fromArrayToCreateDTO(array $data): CreateEstacionDTO
    {
        return new CreateEstacionDTO(
            nombre: $data['nombre'],
            codigo: $data['codigo'],
            direccion: $data['direccion'] ?? null,
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateEstacionDTO
    {
        return new UpdateEstacionDTO(
            nombre: $data['nombre'],
            codigo: $data['codigo'],
            direccion: $data['direccion'] ?? null,
        );
    }
}