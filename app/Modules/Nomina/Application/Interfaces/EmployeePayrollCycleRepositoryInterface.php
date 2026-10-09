<?php

namespace App\Modules\Nomina\Application\Interfaces;

use App\Models\EmployeePayrollCycle;
use Illuminate\Database\Eloquent\Collection;

interface EmployeePayrollCycleRepositoryInterface
{
    public function getByEmployeeId(int $employeeId): Collection;
    public function findById(int $id): ?EmployeePayrollCycle;
    public function create(array $data): EmployeePayrollCycle;
    public function update(int $id, array $data): EmployeePayrollCycle;
    public function changeStatus(int $id, bool $isActive): EmployeePayrollCycle;
    public function checkOverlap(int $employeeId, string $startDate, ?string $endDate, ?int $excludeId = null): bool;
    public function deactivatePreviousActiveAssignments(int $employeeId, int $currentAssignmentId, string $newStartDate): void;
}
