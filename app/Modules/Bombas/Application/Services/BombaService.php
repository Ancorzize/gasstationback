<?php

namespace App\Modules\Bombas\Application\Services;

use App\Models\Bomba;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Bombas\Application\DTOs\CreateBombaDTO;
use App\Modules\Bombas\Application\DTOs\UpdateBombaDTO;
use App\Modules\Bombas\Application\Interfaces\BombaRepositoryInterface;

class BombaService
{
    public function __construct(
        protected BombaRepositoryInterface $bombaRepository
    ) {}

    public function paginate(array $filters = [], int $perPage = 10)
    {
        return $this->bombaRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): Bomba
    {
        $bomba = $this->bombaRepository->findById($id);

        if (!$bomba) {
            throw new HttpException(404, 'Bomba no encontrada.');
        }

        return $bomba;
    }

    public function create(CreateBombaDTO $dto): Bomba
    {
        return $this->bombaRepository->create([
            'estacion_id' => $dto->estacion_id,
            'nombre' => $dto->nombre,
            'codigo' => $dto->codigo,
            'is_active' => true,
        ]);
    }

    public function update(int $id, UpdateBombaDTO $dto): Bomba
    {
        $bomba = $this->findById($id);

        return $this->bombaRepository->update($bomba, [
            'estacion_id' => $dto->estacion_id,
            'nombre' => $dto->nombre,
            'codigo' => $dto->codigo,
        ]);
    }

    public function changeStatus(int $id, bool $isActive): Bomba
    {
        $bomba = $this->findById($id);

        return $this->bombaRepository->changeStatus($bomba, $isActive);
    }
}