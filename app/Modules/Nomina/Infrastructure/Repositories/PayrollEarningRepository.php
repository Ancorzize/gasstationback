<?php

namespace App\Modules\Nomina\Infrastructure\Repositories;

use App\Models\PayrollEarning;
use App\Modules\Nomina\Application\Interfaces\PayrollEarningRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PayrollEarningRepository implements PayrollEarningRepositoryInterface
{
    public function getBySettlementId(int $settlementId): Collection
    {
        return PayrollEarning::where('payroll_settlement_id', $settlementId)->get();
    }

    public function findById(int $id): ?PayrollEarning
    {
        return PayrollEarning::find($id);
    }

    public function findBySettlementAndType(int $settlementId, string $typeCode): ?PayrollEarning
    {
        return PayrollEarning::where('payroll_settlement_id', $settlementId)
            ->where('type_code', $typeCode)
            ->first();
    }

    public function create(array $data): PayrollEarning
    {
        return PayrollEarning::create($data);
    }

    public function update(int $id, array $data): PayrollEarning
    {
        $earning = $this->findById($id);
        $earning->update($data);
        return $earning->fresh();
    }

    public function delete(int $id): bool
    {
        $earning = $this->findById($id);
        return $earning ? $earning->delete() : false;
    }

    public function sumBySettlementId(int $settlementId): float
    {
        return (float) PayrollEarning::where('payroll_settlement_id', $settlementId)->sum('amount');
    }
}
