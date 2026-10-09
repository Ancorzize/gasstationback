<?php

namespace App\Modules\Nomina\Application\Services;

use App\Models\PayrollPeriod;
use App\Modules\Nomina\Application\DTOs\CreatePayrollPeriodDTO;
use App\Modules\Nomina\Application\DTOs\UpdatePayrollPeriodDTO;
use App\Modules\Nomina\Application\Interfaces\PayrollPeriodRepositoryInterface;
use App\Modules\Nomina\Application\Services\PayrollCycleService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PayrollPeriodService
{
    public function __construct(
        protected PayrollPeriodRepositoryInterface $repository,
        protected PayrollCycleService $payrollCycleService
    ) {}

    public function paginate(array $filters = [], int $perPage = 15)
    {
        return $this->repository->getAll($filters, $perPage);
    }

    public function getByCycleId(int $cycleId)
    {
        $this->payrollCycleService->findById($cycleId);
        return $this->repository->getByCycleId($cycleId);
    }

    public function findById(int $id): PayrollPeriod
    {
        $period = $this->repository->findById($id);
        if (!$period) {
            throw new HttpException(404, "Período de nómina #{$id} no encontrado.");
        }
        return $period;
    }

    public function create(CreatePayrollPeriodDTO $dto): PayrollPeriod
    {
        // 1. Validar que el ciclo exista y esté activo
        $cycle = $this->payrollCycleService->findById($dto->payroll_cycle_id);
        if (!$cycle->is_active) {
            throw new HttpException(422, "El ciclo de nómina '{$cycle->name}' se encuentra inactivo. No se pueden crear nuevos períodos.");
        }

        // 2. Validar solapamientos de fechas en el mismo ciclo
        if ($this->repository->checkOverlap($dto->payroll_cycle_id, $dto->start_date, $dto->end_date)) {
            throw new HttpException(422, "El rango de fechas del período se solapa con otro período existente dentro del mismo ciclo.");
        }

        return $this->repository->create($dto->toArray());
    }

    public function update(int $id, UpdatePayrollPeriodDTO $dto): PayrollPeriod
    {
        $period = $this->findById($id);

        $newStartDate = $dto->start_date ?? $period->start_date->format('Y-m-d');
        $newEndDate = $dto->end_date ?? $period->end_date->format('Y-m-d');
        $newPaymentDate = $dto->payment_date ?? $period->payment_date->format('Y-m-d');

        if ($newEndDate < $newStartDate) {
            throw new HttpException(422, "La fecha de fin no puede ser anterior a la fecha de inicio.");
        }

        if ($newPaymentDate < $newEndDate) {
            throw new HttpException(422, "La fecha de pago no puede ser anterior a la fecha de fin.");
        }

        if ($this->repository->checkOverlap($period->payroll_cycle_id, $newStartDate, $newEndDate, $id)) {
            throw new HttpException(422, "El rango de fechas del período se solapa con otro período existente dentro del mismo ciclo.");
        }

        return $this->repository->update($id, $dto->toArray());
    }

    public function changeStatus(int $id, string $status): PayrollPeriod
    {
        $this->findById($id);
        if (!in_array($status, ['open', 'closed', 'cancelled'])) {
            throw new HttpException(422, "Estado de período inválido. Permitidos: open, closed, cancelled.");
        }

        return $this->repository->changeStatus($id, $status);
    }
}
