<?php

namespace App\Modules\Auth\Application\Interfaces;

use App\Models\User;

interface AuthRepositoryInterface
{
    public function findUserByEmail(string $email): ?User;
    public function createToken(User $user, string $tokenName = 'auth_token'): string;
    public function deleteCurrentToken(User $user): void;
}