<?php

namespace App\Modules\Auth\Application\DTOs;

class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}