<?php

namespace App\Modules\Servicios\Infrastructure\Mappers;

use App\Modules\Servicios\Application\DTOs\CreateServicioDTO;
use App\Modules\Servicios\Application\DTOs\UpdateServicioDTO;

class ServicioMapper
{
    public static function fromArrayToCreateDTO(array $data): CreateServicioDTO
    {
        return new CreateServicioDTO(
            codigo: $data['codigo'],
            nombre: $data['nombre'],
            descripcion: $data['descripcion'] ?? null,
            precio: (float) $data['precio'],
            unidad_medida_id: $data['unidad_medida_id'] ?? null,
            permite_decimal: (bool) $data['permite_decimal'],
            duracion_minutos: $data['duracion_minutos'] ?? null,
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateServicioDTO
    {
        return new UpdateServicioDTO(
            codigo: $data['codigo'],
            nombre: $data['nombre'],
            descripcion: $data['descripcion'] ?? null,
            precio: (float) $data['precio'],
            unidad_medida_id: $data['unidad_medida_id'] ?? null,
            permite_decimal: (bool) $data['permite_decimal'],
            duracion_minutos: $data['duracion_minutos'] ?? null,
        );
    }
}