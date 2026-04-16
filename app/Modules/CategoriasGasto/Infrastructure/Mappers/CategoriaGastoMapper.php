<?php

namespace App\Modules\CategoriasGasto\Infrastructure\Mappers;

use App\Modules\CategoriasGasto\Application\DTOs\CreateCategoriaGastoDTO;
use App\Modules\CategoriasGasto\Application\DTOs\UpdateCategoriaGastoDTO;

class CategoriaGastoMapper
{
    public static function fromArrayToCreateDTO(array $data): CreateCategoriaGastoDTO
    {
        return new CreateCategoriaGastoDTO(
            nombre: $data['nombre'],
            descripcion: $data['descripcion'] ?? null,
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateCategoriaGastoDTO
    {
        return new UpdateCategoriaGastoDTO(
            nombre: $data['nombre'],
            descripcion: $data['descripcion'] ?? null,
        );
    }
}