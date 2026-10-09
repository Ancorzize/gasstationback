<?php

namespace App\Modules\Nomina\Application\Services;

use App\Models\EmployeePayrollCycle;
use App\Modules\Nomina\Application\DTOs\CreateEmployeePayrollCycleDTO;
use App\Modules\Nomina\Application\DTOs\UpdateEmployeePayrollCycleDTO;
use App\Modules\Nomina\Application\Interfaces\EmployeePayrollCycleRepositoryInterface;
use App\Modules\Nomina\Application\Services\PayrollCycleService;
use App\Modules\Empleados\Application\Services\EmployeeService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\DB;

class EmployeePayrollCycleService
{
    public function __construct(
        protected EmployeePayrollCycleRepositoryInterface $repository,
        protected EmployeeService $employeeService,
        protected PayrollCycleService $payrollCycleService
    ) {}

    public function getByEmployeeId(int $employeeId)
    {
        $this->employeeService->findById($employeeId);
        return $this->repository->getByEmployeeId($employeeId);
    }

    public function findById(int $id): EmployeePayrollCycle
    {
        $assignment = $this->repository->findById($id);
        if (!$assignment) {
            throw new HttpException(404, "Asignación de ciclo de nómina #{$id} no encontrada.");
        }
        return $assignment;
    }

    public function create(CreateEmployeePayrollCycleDTO $dto): EmployeePayrollCycle
    {
        // 1. Validar que el empleado existe
        $this->employeeService->findById($dto->employee_id);

        // 2. Validar que el ciclo existe y está activo
        $cycle = $this->payrollCycleService->findById($dto->payroll_cycle_id);
        if (!$cycle->is_active) {
            throw new HttpException(422, "El ciclo de nómina '{$cycle->name}' está inactivo y no puede ser asignado.");
        }

        // 3. Validar solapamientos de fechas
        if ($this->repository->checkOverlap($dto->employee_id, $dto->start_date, $dto->end_date)) {
            throw new HttpException(422, "El rango de fechas ingresado se solapa con una asignación de ciclo existente para este empleado.");
        }

        return DB::transaction(function () use ($dto) {
            $assignment = $this->repository->create($dto->toArray());

            if ($assignment->is_active) {
                $this->repository->deactivatePreviousActiveAssignments($dto->employee_id, $assignment->id, $dto->start_date);
            }

            return $assignment;
        });
    }

    public function update(int $id, UpdateEmployeePayrollCycleDTO $dto): EmployeePayrollCycle
    {
        $assignment = $this->findById($id);

        $newCycleId = $dto->payroll_cycle_id ?? $assignment->payroll_cycle_id;
        $newStartDate = $dto->start_date ?? $assignment->start_date->format('Y-m-d');
        $newEndDate = $dto->end_date !== null ? $dto->end_date : ($assignment->end_date?->format('Y-m-d'));

        if ($dto->payroll_cycle_id !== null && $dto->payroll_cycle_id !== $assignment->payroll_cycle_id) {
            $cycle = $this->payrollCycleService->findById($dto->payroll_cycle_id);
            if (!$cycle->is_active) {
                throw new HttpException(422, "El ciclo de nómina '{$cycle->name}' está inactivo y no puede ser asignado.");
            }
        }

        if ($this->repository->checkOverlap($assignment->employee_id, $newStartDate, $newEndDate, $id)) {
            throw new HttpException(422, "El rango de fechas ingresado se solapa con una asignación de ciclo existente para este empleado.");
        }

        return DB::transaction(function () use ($id, $assignment, $dto, $newStartDate) {
            $updated = $this->repository->update($id, $dto->toArray());

            if ($updated->is_active) {
                $this->repository->deactivatePreviousActiveAssignments($updated->employee_id, $updated->id, $newStartDate);
            }

            return $updated;
        });
    }

    public function changeStatus(int $id, bool $isActive): EmployeePayrollCycle
    {
        $assignment = $this->findById($id);

        return DB::transaction(function () use ($id, $assignment, $isActive) {
            $updated = $this->repository->changeStatus($id, $isActive);

            if ($isActive) {
                $startDateStr = $updated->start_date->format('Y-m-d');
                $this->repository->deactivatePreviousActiveAssignments($updated->employee_id, $updated->id, $startDateStr);
            }

            return $updated;
        });
    }
}
