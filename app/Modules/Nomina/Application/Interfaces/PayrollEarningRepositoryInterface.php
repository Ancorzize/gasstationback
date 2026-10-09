<?php

namespace App\Modules\Nomina\Application\Interfaces;

use App\Models\PayrollEarning;
use Illuminate\Database\Eloquent\Collection;

interface PayrollEarningRepositoryInterface
{
    public function getBySettlementId(int $settlementId): Collection;
    public function findById(int $id): ?PayrollEarning;
    public function findBySettlementAndType(int $settlementId, string $typeCode): ?PayrollEarning;
    public function create(array $data): PayrollEarning;
    public function update(int $id, array $data): PayrollEarning;
    public function delete(int $id): bool;
    public function sumBySettlementId(int $settlementId): float;
}
