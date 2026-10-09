<?php

namespace App\Modules\Nomina\Infrastructure\Repositories;

use App\Models\EmployeePayrollCycle;
use App\Modules\Nomina\Application\Interfaces\EmployeePayrollCycleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class EmployeePayrollCycleRepository implements EmployeePayrollCycleRepositoryInterface
{
    public function getByEmployeeId(int $employeeId): Collection
    {
        return EmployeePayrollCycle::with(['payrollCycle'])
            ->where('employee_id', $employeeId)
            ->orderBy('start_date', 'desc')
            ->get();
    }

    public function findById(int $id): ?EmployeePayrollCycle
    {
        return EmployeePayrollCycle::with(['payrollCycle', 'employee'])->find($id);
    }

    public function create(array $data): EmployeePayrollCycle
    {
        return EmployeePayrollCycle::create($data);
    }

    public function update(int $id, array $data): EmployeePayrollCycle
    {
        $assignment = $this->findById($id);
        $assignment->update($data);
        return $assignment->fresh(['payrollCycle', 'employee']);
    }

    public function changeStatus(int $id, bool $isActive): EmployeePayrollCycle
    {
        $assignment = $this->findById($id);
        $assignment->update(['is_active' => $isActive]);
        return $assignment->fresh(['payrollCycle', 'employee']);
    }

    public function checkOverlap(int $employeeId, string $startDate, ?string $endDate, ?int $excludeId = null): bool
    {
        $query = EmployeePayrollCycle::where('employee_id', $employeeId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $query->where(function ($q) use ($startDate, $endDate) {
            $q->where('start_date', '<=', $endDate ?? '9999-12-31')
              ->where(function ($sub) use ($startDate) {
                  $sub->whereNull('end_date')
                      ->orWhere('end_date', '>=', $startDate);
              });
        });

        return $query->exists();
    }

    public function deactivatePreviousActiveAssignments(int $employeeId, int $currentAssignmentId, string $newStartDate): void
    {
        $previousActiveAssignments = EmployeePayrollCycle::where('employee_id', $employeeId)
            ->where('id', '!=', $currentAssignmentId)
            ->where('is_active', true)
            ->get();

        $previousEndDate = Carbon::parse($newStartDate)->subDay()->format('Y-m-d');

        foreach ($previousActiveAssignments as $prev) {
            $updateData = ['is_active' => false];
            if (empty($prev->end_date) || $prev->end_date > $previousEndDate) {
                $updateData['end_date'] = $previousEndDate;
            }
            $prev->update($updateData);
        }
    }
}
