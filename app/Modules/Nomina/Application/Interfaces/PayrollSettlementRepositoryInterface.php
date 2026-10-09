<?php

namespace App\Modules\Nomina\Application\Interfaces;

use App\Models\PayrollSettlement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PayrollSettlementRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getByPeriodId(int $periodId): Collection;
    public function findById(int $id): ?PayrollSettlement;
    public function findByPeriodAndEmployee(int $periodId, int $employeeId): ?PayrollSettlement;
    public function create(array $data): PayrollSettlement;
    public function update(int $id, array $data): PayrollSettlement;
    public function changeStatus(int $id, string $status): PayrollSettlement;
    public function checkEmployeeCycleAssignment(int $employeeId, int $payrollCycleId, string $periodStartDate, string $periodEndDate): bool;
}
