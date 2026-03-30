<?php

namespace App\Modules\Usuarios\Application\Interfaces;

use App\Models\User;

interface UserRepositoryInterface
{
    public function paginate(int $perPage = 10);
    public function findById(int $id): ?User;
    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function changeStatus(User $user, bool $isActive): User;
}