<?php

namespace App\Modules\Proveedores\Application\DTOs;


class CreateProveedorDTO
{
    public function __construct(
        public string $nombre,
        public string $nit,
        public ?string $telefono,
        public ?string $direccion,
        public ?string $email,
    ) {}
}