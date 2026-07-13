<?php

namespace App\Modules\Dashboard\Application\Interfaces;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

interface DashboardConfigRepositoryInterface
{
    public function getRoles(): Collection;

    public function getWidgetsByRole(int $roleId): Collection;

    public function deleteWidgetsByRole(int $roleId): void;

    public function createWidgetRole(array $data): void;

    public function findRoleById(int $roleId): ?Role;
}