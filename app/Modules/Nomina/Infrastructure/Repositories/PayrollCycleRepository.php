<?php

namespace App\Modules\Nomina\Infrastructure\Repositories;

use App\Models\PayrollCycle;
use App\Modules\Nomina\Application\Interfaces\PayrollCycleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PayrollCycleRepository implements PayrollCycleRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PayrollCycle::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (isset($filters['period_type']) && !empty($filters['period_type'])) {
            $query->where('period_type', $filters['period_type']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?PayrollCycle
    {
        return PayrollCycle::find($id);
    }

    public function findByCode(string $code): ?PayrollCycle
    {
        return PayrollCycle::where('code', strtoupper($code))->first();
    }

    public function create(array $data): PayrollCycle
    {
        return PayrollCycle::create($data);
    }

    public function update(int $id, array $data): PayrollCycle
    {
        $cycle = $this->findById($id);
        $cycle->update($data);
        return $cycle->fresh();
    }

    public function changeStatus(int $id, bool $isActive): PayrollCycle
    {
        $cycle = $this->findById($id);
        $cycle->update(['is_active' => $isActive]);
        return $cycle->fresh();
    }
}
