<?php

namespace App\Modules\Roles\Infrastructure\Mappers;

use App\Modules\Roles\Application\DTOs\UpdateRolePermissionsDTO;

class RoleMapper
{
    public static function fromArrayToUpdatePermissionsDTO(array $data): UpdateRolePermissionsDTO
    {
        return new UpdateRolePermissionsDTO(
            permissions: $data['permissions'] ?? [],
        );
    }
}