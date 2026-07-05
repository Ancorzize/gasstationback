<?php

namespace App\Modules\CategoriasProducto\Infrastructure\Mappers;

use App\Modules\CategoriasProducto\Application\DTOs\CreateCategoriaProductoDTO;
use App\Modules\CategoriasProducto\Application\DTOs\UpdateCategoriaProductoDTO;

class CategoriaProductoMapper
{
    public static function fromArrayToCreateDTO(array $data): CreateCategoriaProductoDTO
    {
        return new CreateCategoriaProductoDTO(
            nombre: $data['nombre'],
            descripcion: $data['descripcion'] ?? null,
            destino_recaudo_id: $data['destino_recaudo_id']
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateCategoriaProductoDTO
    {
        return new UpdateCategoriaProductoDTO(
            nombre: $data['nombre'],
            descripcion: $data['descripcion'] ?? null,
            destino_recaudo_id: $data['destino_recaudo_id']
        );
    }
}