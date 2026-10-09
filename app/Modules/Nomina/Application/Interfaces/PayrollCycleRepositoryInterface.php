<?php

namespace App\Modules\Nomina\Application\Interfaces;

use App\Models\PayrollCycle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PayrollCycleRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function findById(int $id): ?PayrollCycle;
    public function findByCode(string $code): ?PayrollCycle;
    public function create(array $data): PayrollCycle;
    public function update(int $id, array $data): PayrollCycle;
    public function changeStatus(int $id, bool $isActive): PayrollCycle;
}
