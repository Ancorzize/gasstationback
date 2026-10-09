<?php

namespace App\Modules\Empleados\Application\Interfaces;

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EmployeeRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function findById(int $id): ?Employee;
    public function create(array $data): Employee;
    public function update(int $id, array $data): Employee;
    public function changeStatus(int $id, bool $isActive): Employee;
}
