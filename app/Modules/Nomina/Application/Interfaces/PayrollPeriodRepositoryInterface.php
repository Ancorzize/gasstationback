<?php

namespace App\Modules\Nomina\Application\Interfaces;

use App\Models\PayrollPeriod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PayrollPeriodRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getByCycleId(int $cycleId): Collection;
    public function findById(int $id): ?PayrollPeriod;
    public function create(array $data): PayrollPeriod;
    public function update(int $id, array $data): PayrollPeriod;
    public function changeStatus(int $id, string $status): PayrollPeriod;
    public function checkOverlap(int $cycleId, string $startDate, string $endDate, ?int $excludeId = null): bool;
}
