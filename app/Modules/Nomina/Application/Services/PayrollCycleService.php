<?php

namespace App\Modules\Nomina\Application\Services;

use App\Models\PayrollCycle;
use App\Modules\Nomina\Application\DTOs\CreatePayrollCycleDTO;
use App\Modules\Nomina\Application\DTOs\UpdatePayrollCycleDTO;
use App\Modules\Nomina\Application\Interfaces\PayrollCycleRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PayrollCycleService
{
    public function __construct(
        protected PayrollCycleRepositoryInterface $repository
    ) {}

    public function paginate(array $filters = [], int $perPage = 15)
    {
        return $this->repository->getAll($filters, $perPage);
    }

    public function findById(int $id): PayrollCycle
    {
        $cycle = $this->repository->findById($id);
        if (!$cycle) {
            throw new HttpException(404, "Ciclo de nómina #{$id} no encontrado.");
        }
        return $cycle;
    }

    public function create(CreatePayrollCycleDTO $dto): PayrollCycle
    {
        $existing = $this->repository->findByCode($dto->code);
        if ($existing) {
            throw new HttpException(422, "El código de ciclo '{$dto->code}' ya está en uso.");
        }

        return $this->repository->create($dto->toArray());
    }

    public function update(int $id, UpdatePayrollCycleDTO $dto): PayrollCycle
    {
        $cycle = $this->findById($id);

        if ($dto->code !== null && strtoupper($dto->code) !== $cycle->code) {
            $existing = $this->repository->findByCode($dto->code);
            if ($existing && $existing->id !== $id) {
                throw new HttpException(422, "El código de ciclo '{$dto->code}' ya está en uso por otro registro.");
            }
        }

        return $this->repository->update($id, $dto->toArray());
    }

    public function changeStatus(int $id, bool $isActive): PayrollCycle
    {
        $this->findById($id);
        return $this->repository->changeStatus($id, $isActive);
    }
}
