<?php

namespace App\Modules\Nomina\Application\Services;

use App\Models\PayrollParameter;
use App\Modules\Nomina\Application\DTOs\CreatePayrollParameterDTO;
use App\Modules\Nomina\Application\DTOs\UpdatePayrollParameterDTO;
use App\Modules\Nomina\Application\Interfaces\PayrollParameterRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PayrollParameterService
{
    public function __construct(
        protected PayrollParameterRepositoryInterface $repository
    ) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getAll($filters, $perPage);
    }

    public function findById(int $id): PayrollParameter
    {
        $parameter = $this->repository->findById($id);
        if (!$parameter) {
            throw new HttpException(404, "Parámetro legal de nómina #{$id} no encontrado.");
        }
        return $parameter;
    }

    public function findByYear(int $year): PayrollParameter
    {
        $parameter = $this->repository->findByYear($year);
        if (!$parameter) {
            throw new HttpException(404, "No existen parámetros legales de nómina configurados para el año {$year}.");
        }
        return $parameter;
    }

    public function create(CreatePayrollParameterDTO $dto): PayrollParameter
    {
        // RN-4B.1-01 / RN-4B.1-05: Unicidad de configuración por año
        $existing = $this->repository->findByYear($dto->year);
        if ($existing) {
            throw new HttpException(422, "Ya existen parámetros legales de nómina registrados para el año {$dto->year}.");
        }

        // RN-4B.1-02: Valores monetarios no negativos
        if ($dto->smlmv < 0) {
            throw new HttpException(422, "El valor del SMLMV no puede ser negativo.");
        }
        if ($dto->transport_assistance < 0) {
            throw new HttpException(422, "El valor del auxilio de transporte no puede ser negativo.");
        }

        // RN-4B.1-03: Límite de SMLMV mayor a cero
        if ($dto->max_smlmv_for_transport <= 0) {
            throw new HttpException(422, "El límite en SMLMV para el auxilio de transporte debe ser mayor a cero.");
        }

        return $this->repository->create($dto->toArray());
    }

    public function update(int $id, UpdatePayrollParameterDTO $dto): PayrollParameter
    {
        $parameter = $this->findById($id);

        // RN-4B.1-06: Protección histórica. Bloquear actualización si existen liquidaciones cerradas para el año
        if ($this->repository->hasClosedSettlementsInYear($parameter->year)) {
            throw new HttpException(422, "No se pueden modificar los parámetros del año {$parameter->year} porque existen liquidaciones de nómina en estado 'closed' para dicho período.");
        }

        // RN-4B.1-02 & RN-4B.1-03: Validaciones numéricas al actualizar
        if ($dto->smlmv !== null && $dto->smlmv < 0) {
            throw new HttpException(422, "El valor del SMLMV no puede ser negativo.");
        }
        if ($dto->transport_assistance !== null && $dto->transport_assistance < 0) {
            throw new HttpException(422, "El valor del auxilio de transporte no puede ser negativo.");
        }
        if ($dto->max_smlmv_for_transport !== null && $dto->max_smlmv_for_transport <= 0) {
            throw new HttpException(422, "El límite en SMLMV para el auxilio de transporte debe ser mayor a cero.");
        }

        return $this->repository->update($id, $dto->toArray());
    }
}
