<?php

namespace App\Modules\Marcas\Application\Services;

use App\Models\Marca;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Marcas\Application\DTOs\CreateMarcaDTO;
use App\Modules\Marcas\Application\DTOs\UpdateMarcaDTO;
use App\Modules\Marcas\Application\Interfaces\MarcaRepositoryInterface;

class MarcaService
{
    public function __construct(
        protected MarcaRepositoryInterface $marcaRepository
    ) {}

    public function paginate(array $filters = [], int $perPage = 10)
    {
        return $this->marcaRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): Marca
    {
        $marca = $this->marcaRepository->findById($id);

        if (!$marca) {
            throw new HttpException(404, 'Marca no encontrada.');
        }

        return $marca;
    }

    public function create(CreateMarcaDTO $dto): Marca
    {
        return $this->marcaRepository->create([
            'nombre' => $dto->nombre,
            'descripcion' => $dto->descripcion,
            'is_active' => true,
        ]);
    }

    public function update(int $id, UpdateMarcaDTO $dto): Marca
    {
        $marca = $this->findById($id);

        return $this->marcaRepository->update($marca, [
            'nombre' => $dto->nombre,
            'descripcion' => $dto->descripcion,
        ]);
    }

    public function changeStatus(int $id, bool $isActive): Marca
    {
        $marca = $this->findById($id);

        return $this->marcaRepository->changeStatus($marca, $isActive);
    }

    public function delete(int $id): void
    {
        $marca = $this->findById($id);

        $this->marcaRepository->delete($marca);
    }
}