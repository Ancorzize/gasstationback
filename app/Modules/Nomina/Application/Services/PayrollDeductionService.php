<?php

namespace App\Modules\Nomina\Application\Services;

use App\Models\PayrollDeduction;
use App\Modules\Nomina\Application\DTOs\CreatePayrollDeductionDTO;
use App\Modules\Nomina\Application\DTOs\UpdatePayrollDeductionDTO;
use App\Modules\Nomina\Application\Interfaces\PayrollDeductionRepositoryInterface;
use App\Modules\Nomina\Application\Interfaces\PayrollSettlementRepositoryInterface;
use App\Modules\Nomina\Domain\Enums\PayrollDeductionTypeEnum;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\DB;

class PayrollDeductionService
{
    public function __construct(
        protected PayrollDeductionRepositoryInterface $deductionRepository,
        protected PayrollSettlementRepositoryInterface $settlementRepository
    ) {}

    public function getBySettlementId(int $settlementId)
    {
        $settlement = $this->settlementRepository->findById($settlementId);
        if (!$settlement) {
            throw new HttpException(404, "Liquidación #{$settlementId} no encontrada.");
        }
        return $this->deductionRepository->getBySettlementId($settlementId);
    }

    public function findById(int $id): PayrollDeduction
    {
        $deduction = $this->deductionRepository->findById($id);
        if (!$deduction) {
            throw new HttpException(404, "Deducción #{$id} no encontrada.");
        }
        return $deduction;
    }

    public function create(CreatePayrollDeductionDTO $dto): PayrollDeduction
    {
        $settlement = $this->settlementRepository->findById($dto->payroll_settlement_id);
        if (!$settlement) {
            throw new HttpException(404, "Liquidación #{$dto->payroll_settlement_id} no encontrada.");
        }

        if (in_array($settlement->status, ['closed', 'cancelled'])) {
            throw new HttpException(422, "No se pueden agregar deducciones a una liquidación en estado '{$settlement->status}'.");
        }

        // Unicidad de conceptos de salud y pensión obligatorios
        if (in_array($dto->type_code, [PayrollDeductionTypeEnum::HEALTH->value, PayrollDeductionTypeEnum::PENSION->value])) {
            $existing = $this->deductionRepository->findBySettlementAndType($dto->payroll_settlement_id, $dto->type_code);
            if ($existing) {
                throw new HttpException(422, "Ya existe un concepto de {$dto->type_code} en esta liquidación.");
            }
        }

        return DB::transaction(function () use ($dto, $settlement) {
            $deduction = $this->deductionRepository->create($dto->toArray());
            $this->recalculateSettlementTotals($settlement->id);
            return $deduction;
        });
    }

    public function update(int $id, UpdatePayrollDeductionDTO $dto): PayrollDeduction
    {
        $deduction = $this->findById($id);
        $settlement = $this->settlementRepository->findById($deduction->payroll_settlement_id);

        if (in_array($settlement->status, ['closed', 'cancelled'])) {
            throw new HttpException(422, "No se pueden modificar deducciones de una liquidación en estado '{$settlement->status}'.");
        }

        return DB::transaction(function () use ($id, $dto, $settlement) {
            $updated = $this->deductionRepository->update($id, $dto->toArray());
            $this->recalculateSettlementTotals($settlement->id);
            return $updated;
        });
    }

    public function delete(int $id): bool
    {
        $deduction = $this->findById($id);
        $settlement = $this->settlementRepository->findById($deduction->payroll_settlement_id);

        if (in_array($settlement->status, ['closed', 'cancelled'])) {
            throw new HttpException(422, "No se pueden eliminar deducciones de una liquidación en estado '{$settlement->status}'.");
        }

        if (in_array($deduction->type_code, [PayrollDeductionTypeEnum::HEALTH->value, PayrollDeductionTypeEnum::PENSION->value])) {
            throw new HttpException(422, "La deducción de {$deduction->type_code} no se puede eliminar de la liquidación.");
        }

        return DB::transaction(function () use ($id, $settlement) {
            $deleted = $this->deductionRepository->delete($id);
            $this->recalculateSettlementTotals($settlement->id);
            return $deleted;
        });
    }

    public function recalculateSettlementTotals(int $settlementId): void
    {
        $deductionsTotal = $this->deductionRepository->sumBySettlementId($settlementId);

        $settlement = $this->settlementRepository->findById($settlementId);
        $totalEarned = $settlement->total_earned;
        $totalVoucher = round($totalEarned - $deductionsTotal, 2);

        $this->settlementRepository->update($settlementId, [
            'deductions_total' => $deductionsTotal,
            'total_voucher' => $totalVoucher,
        ]);
    }
}
