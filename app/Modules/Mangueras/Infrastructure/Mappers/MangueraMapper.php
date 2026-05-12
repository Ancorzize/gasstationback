<?php

namespace App\Modules\Mangueras\Infrastructure\Mappers;

use App\Modules\Mangueras\Application\DTOs\CreateMangueraDTO;
use App\Modules\Mangueras\Application\DTOs\UpdateMangueraDTO;

class MangueraMapper
{
    public static function fromArrayToCreateDTO(array $data): CreateMangueraDTO
    {
        return new CreateMangueraDTO(
            bomba_id: (int) $data['bomba_id'],
            producto_id: (int) $data['producto_id'],
            nombre: $data['nombre'],
            codigo: $data['codigo'],
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateMangueraDTO
    {
        return new UpdateMangueraDTO(
            bomba_id: (int) $data['bomba_id'],
            producto_id: (int) $data['producto_id'],
            nombre: $data['nombre'],
            codigo: $data['codigo'],
        );
    }
}