<?php

namespace App\Modules\Mangueras\Application\Services;

use App\Models\Manguera;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Mangueras\Application\DTOs\CreateMangueraDTO;
use App\Modules\Mangueras\Application\DTOs\UpdateMangueraDTO;
use App\Modules\Mangueras\Application\Interfaces\MangueraRepositoryInterface;

class MangueraService
{
    public function __construct(
        protected MangueraRepositoryInterface $mangueraRepository
    ) {}

    public function paginate(array $filters = [], int $perPage = 10)
    {
        return $this->mangueraRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): Manguera
    {
        $manguera = $this->mangueraRepository->findById($id);

        if (!$manguera) {
            throw new HttpException(404, 'Manguera no encontrada.');
        }

        return $manguera;
    }

    public function create(CreateMangueraDTO $dto): Manguera
    {
        return $this->mangueraRepository->create([
            'bomba_id' => $dto->bomba_id,
            'producto_id' => $dto->producto_id,
            'nombre' => $dto->nombre,
            'codigo' => $dto->codigo,
            'is_active' => true,
        ]);
    }

    public function update(int $id, UpdateMangueraDTO $dto): Manguera
    {
        $manguera = $this->findById($id);

        return $this->mangueraRepository->update($manguera, [
            'bomba_id' => $dto->bomba_id,
            'producto_id' => $dto->producto_id,
            'nombre' => $dto->nombre,
            'codigo' => $dto->codigo,
        ]);
    }

    public function changeStatus(int $id, bool $isActive): Manguera
    {
        $manguera = $this->findById($id);

        return $this->mangueraRepository->changeStatus($manguera, $isActive);
    }
}