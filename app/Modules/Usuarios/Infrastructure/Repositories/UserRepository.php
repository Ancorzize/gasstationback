<?php

namespace App\Modules\Usuarios\Infrastructure\Repositories;

use App\Models\User;
use App\Modules\Usuarios\Application\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function paginate(int $perPage = 10)
    {
        return User::query()
            ->with(['roles', 'bodega'])
            ->paginate($perPage);
    }

    public function findById(int $id): ?User
    {
        return User::with(['roles', 'bodega'])->find($id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh(['roles']);
    }

    public function changeStatus(User $user, bool $isActive): User
    {
        $user->update(['is_active' => $isActive]);
        return $user->fresh(['roles']);
    }
}