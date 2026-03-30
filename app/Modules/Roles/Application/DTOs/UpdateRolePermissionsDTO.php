<?php

namespace App\Modules\Roles\Application\DTOs;

class UpdateRolePermissionsDTO
{
    public function __construct(
        public array $permissions,
    ) {}
}