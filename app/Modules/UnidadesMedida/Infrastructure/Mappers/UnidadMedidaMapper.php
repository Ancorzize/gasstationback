<?php

namespace App\Modules\UnidadesMedida\Infrastructure\Mappers;

use App\Modules\UnidadesMedida\Application\DTOs\CreateUnidadMedidaDTO;
use App\Modules\UnidadesMedida\Application\DTOs\UpdateUnidadMedidaDTO;

class UnidadMedidaMapper
{
    public static function fromArrayToCreateDTO(array $data): CreateUnidadMedidaDTO
    {
        return new CreateUnidadMedidaDTO(
            nombre: $data['nombre'],
            abreviatura: $data['abreviatura'],
            descripcion: $data['descripcion'] ?? null,
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateUnidadMedidaDTO
    {
        return new UpdateUnidadMedidaDTO(
            nombre: $data['nombre'],
            abreviatura: $data['abreviatura'],
            descripcion: $data['descripcion'] ?? null,
        );
    }
}