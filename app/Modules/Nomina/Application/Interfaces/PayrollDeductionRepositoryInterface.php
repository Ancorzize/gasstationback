<?php

namespace App\Modules\Nomina\Application\Interfaces;

use App\Models\PayrollDeduction;
use Illuminate\Database\Eloquent\Collection;

interface PayrollDeductionRepositoryInterface
{
    public function getBySettlementId(int $settlementId): Collection;
    public function findById(int $id): ?PayrollDeduction;
    public function findBySettlementAndType(int $settlementId, string $typeCode): ?PayrollDeduction;
    public function create(array $data): PayrollDeduction;
    public function update(int $id, array $data): PayrollDeduction;
    public function delete(int $id): bool;
    public function sumBySettlementId(int $settlementId): float;
}
