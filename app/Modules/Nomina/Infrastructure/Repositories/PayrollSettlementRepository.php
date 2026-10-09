<?php

namespace App\Modules\Nomina\Infrastructure\Repositories;

use App\Models\PayrollSettlement;
use App\Models\EmployeePayrollCycle;
use App\Modules\Nomina\Application\Interfaces\PayrollSettlementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PayrollSettlementRepository implements PayrollSettlementRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PayrollSettlement::with(['payrollPeriod', 'employee']);

        if (!empty($filters['payroll_period_id'])) {
            $query->where('payroll_period_id', $filters['payroll_period_id']);
        }

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('worker_code', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('first_surname', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function getByPeriodId(int $periodId): Collection
    {
        return PayrollSettlement::with(['payrollPeriod', 'employee'])
            ->where('payroll_period_id', $periodId)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function findById(int $id): ?PayrollSettlement
    {
        return PayrollSettlement::with(['payrollPeriod', 'employee'])->find($id);
    }

    public function findByPeriodAndEmployee(int $periodId, int $employeeId): ?PayrollSettlement
    {
        return PayrollSettlement::where('payroll_period_id', $periodId)
            ->where('employee_id', $employeeId)
            ->first();
    }

    public function create(array $data): PayrollSettlement
    {
        return PayrollSettlement::create($data);
    }

    public function update(int $id, array $data): PayrollSettlement
    {
        $settlement = $this->findById($id);
        $settlement->update($data);
        return $settlement->fresh(['payrollPeriod', 'employee']);
    }

    public function changeStatus(int $id, string $status): PayrollSettlement
    {
        $settlement = $this->findById($id);
        $settlement->update(['status' => $status]);
        return $settlement->fresh(['payrollPeriod', 'employee']);
    }

    public function checkEmployeeCycleAssignment(int $employeeId, int $payrollCycleId, string $periodStartDate, string $periodEndDate): bool
    {
        return EmployeePayrollCycle::where('employee_id', $employeeId)
            ->where('payroll_cycle_id', $payrollCycleId)
            ->where('start_date', '<=', $periodEndDate)
            ->where(function ($q) use ($periodStartDate) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $periodStartDate);
            })
            ->exists();
    }
}
