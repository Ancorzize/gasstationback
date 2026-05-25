<?php

namespace App\Modules\Usuarios\Application\DTOs;

class UpdateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        public string $role,
        public ?int $bodega_id = null,
    ) {}
}