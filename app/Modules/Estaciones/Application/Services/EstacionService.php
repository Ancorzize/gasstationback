<?php

namespace App\Modules\Estaciones\Application\Services;

use App\Models\Estacion;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Estaciones\Application\DTOs\CreateEstacionDTO;
use App\Modules\Estaciones\Application\DTOs\UpdateEstacionDTO;
use App\Modules\Estaciones\Application\Interfaces\EstacionRepositoryInterface;

class EstacionService
{
    public function __construct(
        protected EstacionRepositoryInterface $estacionRepository
    ) {}

    public function paginate(array $filters = [], int $perPage = 10)
    {
        return $this->estacionRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): Estacion
    {
        $estacion = $this->estacionRepository->findById($id);

        if (!$estacion) {
            throw new HttpException(404, 'Estación no encontrada.');
        }

        return $estacion;
    }

    public function create(CreateEstacionDTO $dto): Estacion
    {
        return $this->estacionRepository->create([
            'nombre' => $dto->nombre,
            'codigo' => $dto->codigo,
            'direccion' => $dto->direccion,
            'is_active' => true,
        ]);
    }

    public function update(int $id, UpdateEstacionDTO $dto): Estacion
    {
        $estacion = $this->findById($id);

        return $this->estacionRepository->update($estacion, [
            'nombre' => $dto->nombre,
            'codigo' => $dto->codigo,
            'direccion' => $dto->direccion,
        ]);
    }

    public function changeStatus(int $id, bool $isActive): Estacion
    {
        $estacion = $this->findById($id);

        return $this->estacionRepository->changeStatus($estacion, $isActive);
    }
}