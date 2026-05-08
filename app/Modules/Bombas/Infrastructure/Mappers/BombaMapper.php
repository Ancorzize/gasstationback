<?php

namespace App\Modules\Bombas\Infrastructure\Mappers;

use App\Modules\Bombas\Application\DTOs\CreateBombaDTO;
use App\Modules\Bombas\Application\DTOs\UpdateBombaDTO;

class BombaMapper
{
    public static function fromArrayToCreateDTO(array $data): CreateBombaDTO
    {
        return new CreateBombaDTO(
            estacion_id: (int) $data['estacion_id'],
            nombre: $data['nombre'],
            codigo: $data['codigo'],
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateBombaDTO
    {
        return new UpdateBombaDTO(
            estacion_id: (int) $data['estacion_id'],
            nombre: $data['nombre'],
            codigo: $data['codigo'],
        );
    }
}