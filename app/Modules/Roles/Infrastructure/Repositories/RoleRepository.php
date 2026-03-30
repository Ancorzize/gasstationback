<?php

namespace App\Modules\Roles\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Modules\Roles\Application\Interfaces\RoleRepositoryInterface;

class RoleRepository implements RoleRepositoryInterface
{
    public function getAll(): Collection
    {
        return Role::query()
            ->where('guard_name', 'sanctum')
            ->with('permissions')
            ->orderBy('name')
            ->get();
    }

    public function findById(int $id): ?Role
    {
        return Role::query()
            ->where('guard_name', 'sanctum')
            ->with('permissions')
            ->find($id);
    }

    public function getAllPermissions(): Collection
    {
        return Permission::query()
            ->where('guard_name', 'sanctum')
            ->orderBy('name')
            ->get();
    }

    public function syncPermissions(Role $role, array $permissions): Role
    {
        $role->syncPermissions($permissions);

        return $role->fresh('permissions');
    }
}