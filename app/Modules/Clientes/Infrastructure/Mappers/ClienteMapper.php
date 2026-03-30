<?php

namespace App\Modules\Clientes\Infrastructure\Mappers;

use App\Modules\Clientes\Application\DTOs\CreateClienteDTO;
use App\Modules\Clientes\Application\DTOs\UpdateClienteDTO;

class ClienteMapper
{
    public static function fromArrayToCreateDTO(array $data): CreateClienteDTO
    {
        return new CreateClienteDTO(
            nombre: $data['nombre'],
            apellidos: $data['apellidos'],
            documento: $data['documento'],
            telefono_uno: $data['telefono_uno'] ?? null,
            telefono_dos: $data['telefono_dos'] ?? null,
            direccion: $data['direccion'] ?? null,
            email: $data['email'] ?? null,
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateClienteDTO
    {
        return new UpdateClienteDTO(
            nombre: $data['nombre'],
            apellidos: $data['apellidos'],
            documento: $data['documento'],
            telefono_uno: $data['telefono_uno'] ?? null,
            telefono_dos: $data['telefono_dos'] ?? null,
            direccion: $data['direccion'] ?? null,
            email: $data['email'] ?? null,
        );
    }
}