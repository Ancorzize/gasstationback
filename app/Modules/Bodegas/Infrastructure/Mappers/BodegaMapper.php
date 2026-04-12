<?php

namespace App\Modules\Bodegas\Infrastructure\Mappers;

use App\Modules\Bodegas\Application\DTOs\CreateBodegaDTO;
use App\Modules\Bodegas\Application\DTOs\UpdateBodegaDTO;

class BodegaMapper
{
    public static function fromArrayToCreateDTO(array $data): CreateBodegaDTO
    {
        return new CreateBodegaDTO(
            nombre: $data['nombre'],
            codigo: $data['codigo'],
            descripcion: $data['descripcion'] ?? null,
            direccion: $data['direccion'] ?? null,
            telefono: $data['telefono'] ?? null,
            responsable_id: $data['responsable_id'] ?? null,
            is_principal: (bool) $data['is_principal'],
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateBodegaDTO
    {
        return new UpdateBodegaDTO(
            nombre: $data['nombre'],
            codigo: $data['codigo'],
            descripcion: $data['descripcion'] ?? null,
            direccion: $data['direccion'] ?? null,
            telefono: $data['telefono'] ?? null,
            responsable_id: $data['responsable_id'] ?? null,
            is_principal: (bool) $data['is_principal'],
        );
    }
}