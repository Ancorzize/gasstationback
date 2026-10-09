<?php

namespace App\Modules\Nomina\Application\Services;

use App\Models\PayrollSettlement;
use App\Modules\Nomina\Application\DTOs\CreatePayrollSettlementDTO;
use App\Modules\Nomina\Application\DTOs\UpdatePayrollSettlementDTO;
use App\Modules\Nomina\Application\Interfaces\PayrollSettlementRepositoryInterface;
use App\Modules\Nomina\Application\Interfaces\PayrollEarningRepositoryInterface;
use App\Modules\Nomina\Application\Interfaces\PayrollDeductionRepositoryInterface;
use App\Modules\Nomina\Domain\Enums\PayrollEarningTypeEnum;
use App\Modules\Nomina\Domain\Enums\PayrollDeductionTypeEnum;
use App\Modules\Nomina\Application\Services\PayrollPeriodService;
use App\Modules\Nomina\Application\Services\PayrollParameterService;
use App\Modules\Empleados\Application\Services\EmployeeService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\DB;

class PayrollSettlementService
{
    public function __construct(
        protected PayrollSettlementRepositoryInterface $repository,
        protected PayrollPeriodService $payrollPeriodService,
        protected EmployeeService $employeeService,
        protected PayrollEarningRepositoryInterface $earningRepository,
        protected PayrollDeductionRepositoryInterface $deductionRepository,
        protected PayrollParameterService $parameterService
    ) {}

    public function paginate(array $filters = [], int $perPage = 15)
    {
        return $this->repository->getAll($filters, $perPage);
    }

    public function getByPeriodId(int $periodId)
    {
        $this->payrollPeriodService->findById($periodId);
        return $this->repository->getByPeriodId($periodId);
    }

    public function findById(int $id): PayrollSettlement
    {
        $settlement = $this->repository->findById($id);
        if (!$settlement) {
            throw new HttpException(404, "Liquidación de nómina #{$id} no encontrada.");
        }
        return $settlement;
    }

    public function create(CreatePayrollSettlementDTO $dto): PayrollSettlement
    {
        // 1. Obtener y validar el período de nómina
        $period = $this->payrollPeriodService->findById($dto->payroll_period_id);
        if ($period->status !== 'open') {
            throw new HttpException(422, "El período de nómina '{$period->name}' no está abierto (estado actual: {$period->status}).");
        }

        // 2. Obtener y validar el empleado
        $employee = $this->employeeService->findById($dto->employee_id);

        // Validar vigencia laboral del empleado frente a las fechas del período
        $periodStart = $period->start_date->format('Y-m-d');
        $periodEnd = $period->end_date->format('Y-m-d');

        $dateEntryStr = $employee->date_entry?->format('Y-m-d');
        $departureDateStr = $employee->departure_date?->format('Y-m-d');

        if ($dateEntryStr && $dateEntryStr > $periodEnd) {
            throw new HttpException(422, "La fecha de ingreso del empleado ({$dateEntryStr}) es posterior al período de nómina ({$periodEnd}).");
        }

        if ($departureDateStr && $departureDateStr < $periodStart) {
            throw new HttpException(422, "La fecha de retiro del empleado ({$departureDateStr}) es anterior al período de nómina ({$periodStart}).");
        }

        // 3. Verificar que no exista ya una liquidación para este empleado en este período
        $existing = $this->repository->findByPeriodAndEmployee($dto->payroll_period_id, $dto->employee_id);
        if ($existing) {
            throw new HttpException(422, "Ya existe una liquidación de nómina para el empleado #{$dto->employee_id} en el período #{$dto->payroll_period_id}.");
        }

        // 4. Verificar asignación histórica del empleado al ciclo del período
        $hasCycleAssignment = $this->repository->checkEmployeeCycleAssignment(
            $dto->employee_id,
            $period->payroll_cycle_id,
            $periodStart,
            $periodEnd
        );
        if (!$hasCycleAssignment) {
            throw new HttpException(422, "El empleado no tiene una asignación de ciclo de nómina válida para el ciclo de este período.");
        }

        // 5. Validar días trabajados
        if ($dto->days_worked <= 0) {
            throw new HttpException(422, "Los días trabajados deben ser mayores a 0.");
        }

        // 6. Construir el snapshot del empleado y calcular los valores iniciales
        $salary = $employee->salary;
        $daysWorked = $dto->days_worked;

        $basicSalary = round(($salary / 30) * $daysWorked, 2);
        $year = (int) $period->start_date->year;

        $data = [
            'payroll_period_id' => $dto->payroll_period_id,
            'employee_id' => $dto->employee_id,

            // Snapshot histórico del empleado
            'worker_code' => $employee->worker_code,
            'identity_document_id' => $employee->identity_document_id,
            'document_number' => $employee->document_number,
            'first_name' => $employee->first_name,
            'other_names' => $employee->other_names,
            'first_surname' => $employee->first_surname,
            'second_surname' => $employee->second_surname,
            'worker_type_id' => $employee->worker_type_id,
            'worker_subtype_id' => $employee->worker_subtype_id,
            'contract_type_id' => $employee->contract_type_id,
            'date_entry' => $dateEntryStr,
            'departure_date' => $departureDateStr,
            'salary' => $salary,
            'integral_salary' => (bool) $employee->integral_salary,

            // Datos iniciales de liquidación
            'days_worked' => $daysWorked,
            'basic_salary' => $basicSalary,
            'total_earned' => $basicSalary,
            'deductions_total' => 0.00,
            'total_voucher' => $basicSalary,
            'status' => $dto->status,
        ];

        return DB::transaction(function () use ($data, $basicSalary, $year) {
            $settlement = $this->repository->create($data);

            // Crear devengado automático: BASIC_SALARY
            $this->earningRepository->create([
                'payroll_settlement_id' => $settlement->id,
                'type_code' => PayrollEarningTypeEnum::BASIC_SALARY->value,
                'concept_name' => 'Salario Básico',
                'amount' => $basicSalary,
                'quantity' => $data['days_worked'],
                'is_taxable' => true,
            ]);

            // Crear deducción automática: HEALTH (4%)
            $healthAmount = round($basicSalary * 0.04, 2);
            $this->deductionRepository->create([
                'payroll_settlement_id' => $settlement->id,
                'type_code' => PayrollDeductionTypeEnum::HEALTH->value,
                'concept_name' => 'Aporte a Salud (4%)',
                'percentage' => 4.00,
                'base_amount' => $basicSalary,
                'amount' => $healthAmount,
            ]);

            // Crear deducción automática: PENSION (4%)
            $pensionAmount = round($basicSalary * 0.04, 2);
            $this->deductionRepository->create([
                'payroll_settlement_id' => $settlement->id,
                'type_code' => PayrollDeductionTypeEnum::PENSION->value,
                'concept_name' => 'Aporte a Pensión (4%)',
                'percentage' => 4.00,
                'base_amount' => $basicSalary,
                'amount' => $pensionAmount,
            ]);

            // Evaluar elegibilidad y procesar TRANSPORT_ASSISTANCE
            $this->syncTransportAssistance($settlement, $year);

            // Recalcular y guardar los totales finales
            $totalEarned = $this->earningRepository->sumBySettlementId($settlement->id);
            $deductionsTotal = $this->deductionRepository->sumBySettlementId($settlement->id);
            $totalVoucher = round($totalEarned - $deductionsTotal, 2);

            $this->repository->update($settlement->id, [
                'basic_salary' => $basicSalary,
                'total_earned' => $totalEarned,
                'deductions_total' => $deductionsTotal,
                'total_voucher' => $totalVoucher,
            ]);

            return $settlement->fresh();
        });
    }

    public function update(int $id, UpdatePayrollSettlementDTO $dto): PayrollSettlement
    {
        $settlement = $this->findById($id);

        if (in_array($settlement->status, ['closed', 'cancelled'])) {
            throw new HttpException(422, "No se puede modificar una liquidación en estado '{$settlement->status}'.");
        }

        $updateData = $dto->toArray();

        return DB::transaction(function () use ($id, $updateData, $dto, $settlement) {
            $updatedSettlement = $this->repository->update($id, $updateData);

            $period = $this->payrollPeriodService->findById($settlement->payroll_period_id);
            $year = (int) $period->start_date->year;

            // Si cambian los días trabajados, recalcular básico y deducciones legales sobre la liquidación
            if ($dto->days_worked !== null && $dto->days_worked !== $settlement->days_worked) {
                if ($dto->days_worked <= 0) {
                    throw new HttpException(422, "Los días trabajados deben ser mayores a 0.");
                }

                $daysWorked = $dto->days_worked;
                $snapshotSalary = $settlement->salary;
                $newBasicSalary = round(($snapshotSalary / 30) * $daysWorked, 2);

                // Actualizar devengado BASIC_SALARY
                $basicEarning = $this->earningRepository->findBySettlementAndType($id, PayrollEarningTypeEnum::BASIC_SALARY->value);
                if ($basicEarning) {
                    $this->earningRepository->update($basicEarning->id, [
                        'amount' => $newBasicSalary,
                        'quantity' => $daysWorked,
                    ]);
                }

                // Actualizar deducción HEALTH
                $healthDeduction = $this->deductionRepository->findBySettlementAndType($id, PayrollDeductionTypeEnum::HEALTH->value);
                if ($healthDeduction) {
                    $this->deductionRepository->update($healthDeduction->id, [
                        'base_amount' => $newBasicSalary,
                        'amount' => round($newBasicSalary * 0.04, 2),
                    ]);
                }

                // Actualizar deducción PENSION
                $pensionDeduction = $this->deductionRepository->findBySettlementAndType($id, PayrollDeductionTypeEnum::PENSION->value);
                if ($pensionDeduction) {
                    $this->deductionRepository->update($pensionDeduction->id, [
                        'base_amount' => $newBasicSalary,
                        'amount' => round($newBasicSalary * 0.04, 2),
                    ]);
                }
            }

            // Evaluar elegibilidad y sincronizar TRANSPORT_ASSISTANCE
            $this->syncTransportAssistance($updatedSettlement, $year);

            // Recalcular y guardar totales finales de la liquidación
            $totalEarned = $this->earningRepository->sumBySettlementId($id);
            $deductionsTotal = $this->deductionRepository->sumBySettlementId($id);
            $totalVoucher = round($totalEarned - $deductionsTotal, 2);

            $basicEarning = $this->earningRepository->findBySettlementAndType($id, PayrollEarningTypeEnum::BASIC_SALARY->value);
            $basicSalaryVal = $basicEarning ? $basicEarning->amount : 0.00;

            $updatedSettlement = $this->repository->update($id, [
                'basic_salary' => $basicSalaryVal,
                'total_earned' => $totalEarned,
                'deductions_total' => $deductionsTotal,
                'total_voucher' => $totalVoucher,
            ]);

            return $updatedSettlement->fresh();
        });
    }

    public function changeStatus(int $id, string $status): PayrollSettlement
    {
        $settlement = $this->findById($id);

        if (!in_array($status, ['draft', 'calculated', 'closed', 'cancelled'])) {
            throw new HttpException(422, "Estado inválido. Permitidos: draft, calculated, closed, cancelled.");
        }

        if ($settlement->status === 'closed' && $status !== 'closed') {
            throw new HttpException(422, "Una liquidación en estado 'closed' no puede cambiar a otro estado.");
        }

        if ($settlement->status === 'cancelled' && $status !== 'cancelled') {
            throw new HttpException(422, "Una liquidación en estado 'cancelled' no puede cambiar a otro estado.");
        }

        return $this->repository->changeStatus($id, $status);
    }

    protected function syncTransportAssistance(PayrollSettlement $settlement, int $year): void
    {
        try {
            $parameter = $this->parameterService->findByYear($year);
        } catch (HttpException $e) {
            if ($e->getStatusCode() === 404) {
                throw new HttpException(422, "No existen parámetros legales de nómina configurados para el año {$year}.");
            }
            throw $e;
        }

        $smlmv = $parameter->smlmv;
        $maxSmlmv = $parameter->max_smlmv_for_transport;
        $transportLimit = round($smlmv * $maxSmlmv, 2);

        $snapshotSalary = $settlement->salary;
        $integralSalary = (bool) $settlement->integral_salary;

        // Regla de elegibilidad: integral_salary == false && snapshot_salary <= smlmv * max_smlmv_for_transport
        $isEligible = (!$integralSalary) && ($snapshotSalary <= $transportLimit);

        $existingEarning = $this->earningRepository->findBySettlementAndType(
            $settlement->id,
            PayrollEarningTypeEnum::TRANSPORT_ASSISTANCE->value
        );

        if ($isEligible) {
            $daysWorked = (int) $settlement->days_worked;
            $monthlyTransport = $parameter->transport_assistance;
            $amount = round(($monthlyTransport / 30) * $daysWorked, 2);

            if ($existingEarning) {
                $this->earningRepository->update($existingEarning->id, [
                    'concept_name' => 'Auxilio de Transporte',
                    'quantity' => $daysWorked,
                    'unit_value' => $monthlyTransport,
                    'amount' => $amount,
                    'is_taxable' => false,
                ]);
            } else {
                $this->earningRepository->create([
                    'payroll_settlement_id' => $settlement->id,
                    'type_code' => PayrollEarningTypeEnum::TRANSPORT_ASSISTANCE->value,
                    'concept_name' => 'Auxilio de Transporte',
                    'quantity' => $daysWorked,
                    'unit_value' => $monthlyTransport,
                    'amount' => $amount,
                    'is_taxable' => false,
                ]);
            }
        } else {
            if ($existingEarning) {
                $this->earningRepository->delete($existingEarning->id);
            }
        }
    }
}
