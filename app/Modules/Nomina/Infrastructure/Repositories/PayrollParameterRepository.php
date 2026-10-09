<?php

namespace App\Modules\Nomina\Infrastructure\Repositories;

use App\Models\PayrollParameter;
use App\Models\PayrollSettlement;
use App\Modules\Nomina\Application\Interfaces\PayrollParameterRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PayrollParameterRepository implements PayrollParameterRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PayrollParameter::query();

        if (!empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        return $query->orderBy('year', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?PayrollParameter
    {
        return PayrollParameter::find($id);
    }

    public function findByYear(int $year): ?PayrollParameter
    {
        return PayrollParameter::where('year', $year)->first();
    }

    public function create(array $data): PayrollParameter
    {
        return PayrollParameter::create($data);
    }

    public function update(int $id, array $data): PayrollParameter
    {
        $parameter = $this->findById($id);
        $parameter->update($data);
        return $parameter->fresh();
    }

    public function hasClosedSettlementsInYear(int $year): bool
    {
        return PayrollSettlement::where('status', 'closed')
            ->whereHas('payrollPeriod', function ($query) use ($year) {
                $query->whereYear('start_date', $year);
            })
            ->exists();
    }
}
