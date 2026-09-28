<?php

namespace App\Modules\Clientes\Application\DTOs;

class CreateClienteDTO
{
    public function __construct(
        public string $nombre,
        public string $apellidos,
        public string $documento,
        public ?string $telefono_uno,
        public ?string $telefono_dos,
        public ?string $direccion,
        public ?string $email,
        public ?string $tipo_persona = null,
        public ?string $tipo_documento_id = null,
        public ?string $tipo_organization_id = null,
        public ?string $tax_regime_id = null,
        public ?string $tax_level_id = null,
        public ?string $codigo_postal = null,
        public ?int $ciudad_id = null,
        public ?int $pais_id = null,
    ) {}
}