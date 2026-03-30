<?php

namespace App\Modules\Auth\Infrastructure\Mappers;

use App\Modules\Auth\Application\DTOs\LoginDTO;

class AuthMapper
{
    public static function fromArrayToLoginDTO(array $data): LoginDTO
    {
        return new LoginDTO(
            email: $data['email'],
            password: $data['password'],
        );
    }
}