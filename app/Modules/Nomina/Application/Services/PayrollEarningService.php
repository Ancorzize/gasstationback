<?php

namespace App\Modules\Nomina\Application\Services;

use App\Models\PayrollEarning;
use App\Modules\Nomina\Application\DTOs\CreatePayrollEarningDTO;
use App\Modules\Nomina\Application\DTOs\UpdatePayrollEarningDTO;
use App\Modules\Nomina\Application\Interfaces\PayrollEarningRepositoryInterface;
use App\Modules\Nomina\Application\Interfaces\PayrollSettlementRepositoryInterface;
use App\Modules\Nomina\Domain\Enums\PayrollEarningTypeEnum;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\DB;

class PayrollEarningService
{
    public function __construct(
        protected PayrollEarningRepositoryInterface $earningRepository,
        protected PayrollSettlementRepositoryInterface $settlementRepository
    ) {}

    public function getBySettlementId(int $settlementId)
    {
        $settlement = $this->settlementRepository->findById($settlementId);
        if (!$settlement) {
            throw new HttpException(404, "Liquidación #{$settlementId} no encontrada.");
        }
        return $this->earningRepository->getBySettlementId($settlementId);
    }

    public function findById(int $id): PayrollEarning
    {
        $earning = $this->earningRepository->findById($id);
        if (!$earning) {
            throw new HttpException(404, "Devengado #{$id} no encontrado.");
        }
        return $earning;
    }

    public function create(CreatePayrollEarningDTO $dto): PayrollEarning
    {
        $settlement = $this->settlementRepository->findById($dto->payroll_settlement_id);
        if (!$settlement) {
            throw new HttpException(404, "Liquidación #{$dto->payroll_settlement_id} no encontrada.");
        }

        if (in_array($settlement->status, ['closed', 'cancelled'])) {
            throw new HttpException(422, "No se pueden agregar devengados a una liquidación en estado '{$settlement->status}'.");
        }

        // Bloquear creación manual de conceptos automáticos
        if ($dto->type_code === PayrollEarningTypeEnum::BASIC_SALARY->value) {
            $existing = $this->earningRepository->findBySettlementAndType($dto->payroll_settlement_id, $dto->type_code);
            if ($existing) {
                throw new HttpException(422, "Ya existe un concepto de Salario Básico (BASIC_SALARY) en esta liquidación.");
            }
        }

        if ($dto->type_code === PayrollEarningTypeEnum::TRANSPORT_ASSISTANCE->value) {
            throw new HttpException(422, "El devengado de Auxilio de Transporte (TRANSPORT_ASSISTANCE) se calcula automáticamente y no se puede crear manualmente.");
        }

        return DB::transaction(function () use ($dto, $settlement) {
            $earning = $this->earningRepository->create($dto->toArray());
            $this->recalculateSettlementTotals($settlement->id);
            return $earning;
        });
    }

    public function update(int $id, UpdatePayrollEarningDTO $dto): PayrollEarning
    {
        $earning = $this->findById($id);
        $settlement = $this->settlementRepository->findById($earning->payroll_settlement_id);

        if (in_array($settlement->status, ['closed', 'cancelled'])) {
            throw new HttpException(422, "No se pueden modificar devengados de una liquidación en estado '{$settlement->status}'.");
        }

        if ($earning->type_code === PayrollEarningTypeEnum::TRANSPORT_ASSISTANCE->value) {
            throw new HttpException(422, "El devengado de Auxilio de Transporte (TRANSPORT_ASSISTANCE) se calcula automáticamente y no se puede editar manualmente.");
        }

        return DB::transaction(function () use ($id, $dto, $settlement) {
            $updated = $this->earningRepository->update($id, $dto->toArray());
            $this->recalculateSettlementTotals($settlement->id);
            return $updated;
        });
    }

    public function delete(int $id): bool
    {
        $earning = $this->findById($id);
        $settlement = $this->settlementRepository->findById($earning->payroll_settlement_id);

        if (in_array($settlement->status, ['closed', 'cancelled'])) {
            throw new HttpException(422, "No se pueden eliminar devengados de una liquidación en estado '{$settlement->status}'.");
        }

        if ($earning->type_code === PayrollEarningTypeEnum::BASIC_SALARY->value) {
            throw new HttpException(422, "El devengado de Salario Básico (BASIC_SALARY) no se puede eliminar de la liquidación.");
        }

        if ($earning->type_code === PayrollEarningTypeEnum::TRANSPORT_ASSISTANCE->value) {
            throw new HttpException(422, "El devengado de Auxilio de Transporte (TRANSPORT_ASSISTANCE) se calcula automáticamente y no se puede eliminar manualmente.");
        }

        return DB::transaction(function () use ($id, $settlement) {
            $deleted = $this->earningRepository->delete($id);
            $this->recalculateSettlementTotals($settlement->id);
            return $deleted;
        });
    }

    public function recalculateSettlementTotals(int $settlementId): void
    {
        $totalEarned = $this->earningRepository->sumBySettlementId($settlementId);

        $basicEarning = $this->earningRepository->findBySettlementAndType($settlementId, PayrollEarningTypeEnum::BASIC_SALARY->value);
        $basicSalary = $basicEarning ? $basicEarning->amount : 0.00;

        $settlement = $this->settlementRepository->findById($settlementId);
        $deductionsTotal = $settlement->deductions_total;
        $totalVoucher = round($totalEarned - $deductionsTotal, 2);

        $this->settlementRepository->update($settlementId, [
            'basic_salary' => $basicSalary,
            'total_earned' => $totalEarned,
            'total_voucher' => $totalVoucher,
        ]);
    }
}
