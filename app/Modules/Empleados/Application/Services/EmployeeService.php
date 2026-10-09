<?php

namespace App\Modules\Empleados\Application\Services;

use App\Models\Employee;
use App\Modules\Empleados\Application\DTOs\CreateEmployeeDTO;
use App\Modules\Empleados\Application\DTOs\UpdateEmployeeDTO;
use App\Modules\Empleados\Application\Interfaces\EmployeeRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EmployeeService
{
    public function __construct(
        protected EmployeeRepositoryInterface $repository
    ) {}

    public function paginate(array $filters = [], int $perPage = 15)
    {
        return $this->repository->getAll($filters, $perPage);
    }

    public function findById(int $id): Employee
    {
        $employee = $this->repository->findById($id);
        if (!$employee) {
            throw new HttpException(404, "Empleado #{$id} no encontrado.");
        }
        return $employee;
    }

    public function create(CreateEmployeeDTO $dto): Employee
    {
        return $this->repository->create($dto->toArray());
    }

    public function update(int $id, UpdateEmployeeDTO $dto): Employee
    {
        $this->findById($id);
        return $this->repository->update($id, $dto->toArray());
    }

    public function changeStatus(int $id, bool $isActive): Employee
    {
        $this->findById($id);
        return $this->repository->changeStatus($id, $isActive);
    }
}
