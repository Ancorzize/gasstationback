<?php

namespace App\Modules\Empleados\Infrastructure\Repositories;

use App\Models\Employee;
use App\Modules\Empleados\Application\Interfaces\EmployeeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Employee::with('user')->orderBy('id', 'desc');

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('worker_code', 'like', "%{$term}%")
                  ->orWhere('document_number', 'like', "%{$term}%")
                  ->orWhere('first_name', 'like', "%{$term}%")
                  ->orWhere('first_surname', 'like', "%{$term}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Employee
    {
        return Employee::with('user')->find($id);
    }

    public function create(array $data): Employee
    {
        return Employee::create($data);
    }

    public function update(int $id, array $data): Employee
    {
        $employee = Employee::findOrFail($id);
        $employee->update($data);
        return $employee->fresh(['user']);
    }

    public function changeStatus(int $id, bool $isActive): Employee
    {
        $employee = Employee::findOrFail($id);
        $employee->update(['is_active' => $isActive]);
        return $employee;
    }
}
