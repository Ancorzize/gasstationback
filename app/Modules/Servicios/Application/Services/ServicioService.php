<?php

namespace App\Modules\Servicios\Application\Services;

use App\Models\Servicio;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Servicios\Application\DTOs\CreateServicioDTO;
use App\Modules\Servicios\Application\DTOs\UpdateServicioDTO;
use App\Modules\Servicios\Application\Interfaces\ServicioRepositoryInterface;

class ServicioService
{
    public function __construct(
        protected ServicioRepositoryInterface $repository
    ) {}

    public function paginate(array $filters = [], int $perPage = 10)
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function findById(int $id): Servicio
    {
        $servicio = $this->repository->findById($id);

        if (!$servicio) {
            throw new HttpException(404, 'Servicio no encontrado.');
        }

        return $servicio;
    }

    public function create(CreateServicioDTO $dto): Servicio
    {
        return $this->repository->create([
            'codigo' => $dto->codigo,
            'nombre' => $dto->nombre,
            'descripcion' => $dto->descripcion,
            'precio' => $dto->precio,
            'unidad_medida_id' => $dto->unidad_medida_id,
            'permite_decimal' => $dto->permite_decimal,
            'duracion_minutos' => $dto->duracion_minutos,
            'is_active' => true,
        ]);
    }

    public function update(int $id, UpdateServicioDTO $dto): Servicio
    {
        $servicio = $this->findById($id);

        return $this->repository->update($servicio, [
            'codigo' => $dto->codigo,
            'nombre' => $dto->nombre,
            'descripcion' => $dto->descripcion,
            'precio' => $dto->precio,
            'unidad_medida_id' => $dto->unidad_medida_id,
            'permite_decimal' => $dto->permite_decimal,
            'duracion_minutos' => $dto->duracion_minutos,
        ]);
    }

    public function changeStatus(int $id, bool $isActive): Servicio
    {
        $servicio = $this->findById($id);

        return $this->repository->changeStatus($servicio, $isActive);
    }

    public function delete(int $id): void
    {
        $servicio = $this->findById($id);

        $this->repository->delete($servicio);
    }
}