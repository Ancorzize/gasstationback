<?php

namespace App\Modules\Usuarios\Infrastructure\Mappers;

use App\Modules\Usuarios\Application\DTOs\CreateUserDTO;
use App\Modules\Usuarios\Application\DTOs\UpdateUserDTO;

class UserMapper
{
    public static function fromArrayToCreateDTO(array $data): CreateUserDTO
    {
        return new CreateUserDTO(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            role: $data['role'],
        );
    }

    public static function fromArrayToUpdateDTO(array $data): UpdateUserDTO
    {
        return new UpdateUserDTO(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'] ?? null,
            role: $data['role'],
        );
    }
}