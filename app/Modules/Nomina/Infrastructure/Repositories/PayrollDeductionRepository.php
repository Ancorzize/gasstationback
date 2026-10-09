<?php

namespace App\Modules\Nomina\Infrastructure\Repositories;

use App\Models\PayrollDeduction;
use App\Modules\Nomina\Application\Interfaces\PayrollDeductionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PayrollDeductionRepository implements PayrollDeductionRepositoryInterface
{
    public function getBySettlementId(int $settlementId): Collection
    {
        return PayrollDeduction::where('payroll_settlement_id', $settlementId)->get();
    }

    public function findById(int $id): ?PayrollDeduction
    {
        return PayrollDeduction::find($id);
    }

    public function findBySettlementAndType(int $settlementId, string $typeCode): ?PayrollDeduction
    {
        return PayrollDeduction::where('payroll_settlement_id', $settlementId)
            ->where('type_code', $typeCode)
            ->first();
    }

    public function create(array $data): PayrollDeduction
    {
        return PayrollDeduction::create($data);
    }

    public function update(int $id, array $data): PayrollDeduction
    {
        $deduction = $this->findById($id);
        $deduction->update($data);
        return $deduction->fresh();
    }

    public function delete(int $id): bool
    {
        $deduction = $this->findById($id);
        return $deduction ? $deduction->delete() : false;
    }

    public function sumBySettlementId(int $settlementId): float
    {
        return (float) PayrollDeduction::where('payroll_settlement_id', $settlementId)->sum('amount');
    }
}
