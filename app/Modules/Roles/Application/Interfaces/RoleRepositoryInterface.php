<?php

namespace App\Modules\Roles\Application\Interfaces;

use Spatie\Permission\Models\Role;
use Illuminate\Support\Collection;

interface RoleRepositoryInterface
{
    public function getAll(): Collection;
    public function findById(int $id): ?Role;
    public function getAllPermissions(): Collection;
    public function syncPermissions(Role $role, array $permissions): Role;
}