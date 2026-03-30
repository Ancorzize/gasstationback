<?php

namespace App\Modules\Clientes\Application\DTOs;

class UpdateClienteDTO
{
    public function __construct(
        public string $nombre,
        public string $apellidos,
        public string $documento,
        public ?string $telefono_uno,
        public ?string $telefono_dos,
        public ?string $direccion,
        public ?string $email,
    ) {}
}