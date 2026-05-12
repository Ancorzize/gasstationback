<?php

namespace App\Modules\PreciosCombustible\Infrastructure\Mappers;

use App\Modules\PreciosCombustible\Application\DTOs\CreatePrecioCombustibleDTO;

class PrecioCombustibleMapper
{
    public static function fromArrayToCreateDTO(array $data): CreatePrecioCombustibleDTO
    {
        return new CreatePrecioCombustibleDTO(
            producto_id: (int) $data['producto_id'],
            precio: (float) $data['precio'],
            fecha_inicio: $data['fecha_inicio'] ?? null,
        );
    }
}