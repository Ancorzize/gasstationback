<?php

namespace App\Modules\Auth\Infrastructure\Repositories;

use App\Models\User;
use App\Modules\Auth\Application\Interfaces\AuthRepositoryInterface;

class AuthRepository implements AuthRepositoryInterface
{
    public function findUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function createToken(User $user, string $tokenName = 'auth_token'): string
    {
        return $user->createToken($tokenName)->plainTextToken;
    }

    public function deleteCurrentToken(User $user): void
    {
        $user->tokens()->delete();
    }
}