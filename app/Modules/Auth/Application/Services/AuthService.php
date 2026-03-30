<?php

namespace App\Modules\Auth\Application\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Auth\Application\DTOs\LoginDTO;
use App\Modules\Auth\Application\Interfaces\AuthRepositoryInterface;

class AuthService
{
    public function __construct(
        protected AuthRepositoryInterface $authRepository
    ) {}

    public function login(LoginDTO $dto): array
    {
        $user = $this->authRepository->findUserByEmail($dto->email);

        if (!$user || !Hash::check($dto->password, $user->password)) {
            throw new HttpException(401, 'Credenciales incorrectas.');
        }

        if (!$user->is_active) {
            throw new HttpException(403, 'El usuario está inactivo.');
        }

        $token = $this->authRepository->createToken($user);

        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    public function logout(User $user): void
    {
        $this->authRepository->deleteCurrentToken($user);
    }

    public function me(User $user): User
    {
        return $user;
    }
}