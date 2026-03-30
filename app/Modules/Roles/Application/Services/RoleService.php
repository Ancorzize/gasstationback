<?php

namespace App\Modules\Roles\Application\Services;

use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Spatie\Permission\Models\Role;
use App\Modules\Roles\Application\DTOs\UpdateRolePermissionsDTO;
use App\Modules\Roles\Application\Interfaces\RoleRepositoryInterface;

class RoleService
{
    public function __construct(
        protected RoleRepositoryInterface $roleRepository
    ) {}

    public function getAll(): Collection
    {
        return $this->roleRepository->getAll();
    }

    public function findById(int $id): Role
    {
        $role = $this->roleRepository->findById($id);

        if (!$role) {
            throw new HttpException(404, 'Rol no encontrado.');
        }

        return $role;
    }

    public function getAllPermissions(): Collection
    {
        return $this->roleRepository->getAllPermissions();
    }

    public function getPermissionsGrouped(): array
    {
        $permissions = $this->roleRepository->getAllPermissions();

        return $permissions
            ->groupBy(function ($permission) {
                $name = $permission->name;
                $parts = explode('_', $name);

                return count($parts) > 1 ? $parts[1] : 'general';
            })
            ->map(function ($group, $module) {
                return [
                    'module' => $module,
                    'permissions' => $group->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                        ];
                    })->values(),
                ];
            })
            ->values()
            ->toArray();
    }

    public function updatePermissions(int $id, UpdateRolePermissionsDTO $dto): Role
    {
        $role = $this->findById($id);

        return $this->roleRepository->syncPermissions($role, $dto->permissions);
    }

    public function getAuthUserPermissions($user): array
    {
        return $user->getAllPermissions()
            ->pluck('name')
            ->values()
            ->toArray();
    }
}