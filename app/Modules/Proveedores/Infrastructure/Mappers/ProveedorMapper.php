<?php

namespace App\Modules\Proveedores\Infrastructure\Mappers;

use App\Modules\Proveedores\Application\DTOs\CreateProveedorDTO;
use App\Modules\Proveedores\Application\DTOs\UpdateProveedorDTO;

class ProveedorMapper
{
    public static function fromArrayToCreateDTO(array $data): CreateProveedorDTO
    {
        return new CreateProveedorDTO(
            nombre: $data['nombre'],
            nit: $data['nit'],
            telefono: $data['telefono'] ?? null,
            direccion: $data['direccion'] ?? null,
            email: $data['email'] ?? null,
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateProveedorDTO
    {
        return new UpdateProveedorDTO(
            nombre: $data['nombre'],
            nit: $data['nit'],
            telefono: $data['telefono'] ?? null,
            direccion: $data['direccion'] ?? null,
            email: $data['email'] ?? null,
        );
    }
}