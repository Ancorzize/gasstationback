<?php

namespace App\Modules\Nomina\Application\Interfaces;

use App\Models\PayrollParameter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PayrollParameterRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function findById(int $id): ?PayrollParameter;
    public function findByYear(int $year): ?PayrollParameter;
    public function create(array $data): PayrollParameter;
    public function update(int $id, array $data): PayrollParameter;
    public function hasClosedSettlementsInYear(int $year): bool;
}
