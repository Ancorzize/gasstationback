<?php

namespace App\Modules\Usuarios\Application\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Usuarios\Application\DTOs\CreateUserDTO;
use App\Modules\Usuarios\Application\DTOs\UpdateUserDTO;
use App\Modules\Usuarios\Application\Interfaces\UserRepositoryInterface;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    public function paginate(int $perPage = 10)
    {
        return $this->userRepository->paginate($perPage);
    }

    public function findById(int $id): User
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            throw new HttpException(404, 'Usuario no encontrado.');
        }

        return $user;
    }

    public function create(CreateUserDTO $dto): User
    {
        return DB::transaction(function () use ($dto) {
            $user = $this->userRepository->create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => $dto->password,
                'bodega_id' => $dto->bodega_id,
                'is_active' => true,
            ]);

            $user->syncRoles([$dto->role]);

            return $user->fresh(['roles']);
        });
    }

    public function update(int $id, UpdateUserDTO $dto): User
    {
        return DB::transaction(function () use ($id, $dto) {
            $user = $this->findById($id);

            $data = [
                'name' => $dto->name,
                'email' => $dto->email,
                'bodega_id' => $dto->bodega_id,
            ];

            if (!empty($dto->password)) {
                $data['password'] = $dto->password;
            }

            $user = $this->userRepository->update($user, $data);
            $user->syncRoles([$dto->role]);

            return $user->fresh(['roles']);
        });
    }

    public function changeStatus(int $id, bool $isActive): User
    {
        $user = $this->findById($id);

        return $this->userRepository->changeStatus($user, $isActive);
    }
}