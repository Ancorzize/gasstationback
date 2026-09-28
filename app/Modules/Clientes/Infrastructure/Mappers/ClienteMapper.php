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
            tipo_persona: $data['tipo_persona'] ?? null,
            tipo_documento_id: isset($data['tipo_documento_id']) ? (int) $data['tipo_documento_id'] : null,
            tipo_organization_id: isset($data['tipo_organization_id']) ? (int) $data['tipo_organization_id'] : null,
            tax_regime_id: isset($data['tax_regime_id']) ? (int) $data['tax_regime_id'] : null,
            tax_level_id: isset($data['tax_level_id']) ? (int) $data['tax_level_id'] : null,
            codigo_postal: $data['codigo_postal'] ?? null,
            ciudad_id: isset($data['ciudad_id']) ? (int) $data['ciudad_id'] : null,
            pais_id: isset($data['pais_id']) ? (int) $data['pais_id'] : null,
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
            tipo_persona: $data['tipo_persona'] ?? null,
            tipo_documento_id: isset($data['tipo_documento_id']) ? (int) $data['tipo_documento_id'] : null,
            tipo_organization_id: isset($data['tipo_organization_id']) ? (int) $data['tipo_organization_id'] : null,
            tax_regime_id: isset($data['tax_regime_id']) ? (int) $data['tax_regime_id'] : null,
            tax_level_id: isset($data['tax_level_id']) ? (int) $data['tax_level_id'] : null,
            codigo_postal: $data['codigo_postal'] ?? null,
            ciudad_id: isset($data['ciudad_id']) ? (int) $data['ciudad_id'] : null,
            pais_id: isset($data['pais_id']) ? (int) $data['pais_id'] : null,
        );
    }
}