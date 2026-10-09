<?php

namespace App\Modules\Nomina\Infrastructure\Repositories;

use App\Models\PayrollPeriod;
use App\Modules\Nomina\Application\Interfaces\PayrollPeriodRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PayrollPeriodRepository implements PayrollPeriodRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PayrollPeriod::with(['payrollCycle']);

        if (!empty($filters['payroll_cycle_id'])) {
            $query->where('payroll_cycle_id', $filters['payroll_cycle_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('start_date', 'desc')->paginate($perPage);
    }

    public function getByCycleId(int $cycleId): Collection
    {
        return PayrollPeriod::with(['payrollCycle'])
            ->where('payroll_cycle_id', $cycleId)
            ->orderBy('start_date', 'desc')
            ->get();
    }

    public function findById(int $id): ?PayrollPeriod
    {
        return PayrollPeriod::with(['payrollCycle'])->find($id);
    }

    public function create(array $data): PayrollPeriod
    {
        return PayrollPeriod::create($data);
    }

    public function update(int $id, array $data): PayrollPeriod
    {
        $period = $this->findById($id);
        $period->update($data);
        return $period->fresh(['payrollCycle']);
    }

    public function changeStatus(int $id, string $status): PayrollPeriod
    {
        $period = $this->findById($id);
        $period->update(['status' => $status]);
        return $period->fresh(['payrollCycle']);
    }

    public function checkOverlap(int $cycleId, string $startDate, string $endDate, ?int $excludeId = null): bool
    {
        $query = PayrollPeriod::where('payroll_cycle_id', $cycleId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $query->where(function ($q) use ($startDate, $endDate) {
            $q->where('start_date', '<=', $endDate)
              ->where('end_date', '>=', $startDate);
        });

        return $query->exists();
    }
}
