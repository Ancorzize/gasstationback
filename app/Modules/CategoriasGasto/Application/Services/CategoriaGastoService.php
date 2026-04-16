<?php

namespace App\Modules\CategoriasGasto\Application\Services;

use App\Models\CategoriaGasto;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\CategoriasGasto\Application\DTOs\CreateCategoriaGastoDTO;
use App\Modules\CategoriasGasto\Application\DTOs\UpdateCategoriaGastoDTO;
use App\Modules\CategoriasGasto\Application\Interfaces\CategoriaGastoRepositoryInterface;

class CategoriaGastoService
{
    public function __construct(
        protected CategoriaGastoRepositoryInterface $categoriaGastoRepository
    ) {}

    public function paginate(array $filters = [], int $perPage = 10)
    {
        return $this->categoriaGastoRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): CategoriaGasto
    {
        $categoria = $this->categoriaGastoRepository->findById($id);

        if (!$categoria) {
            throw new HttpException(404, 'Categoría de gasto no encontrada.');
        }

        return $categoria;
    }

    public function create(CreateCategoriaGastoDTO $dto): CategoriaGasto
    {
        return $this->categoriaGastoRepository->create([
            'nombre' => $dto->nombre,
            'descripcion' => $dto->descripcion,
            'is_active' => true,
        ]);
    }

    public function update(int $id, UpdateCategoriaGastoDTO $dto): CategoriaGasto
    {
        $categoria = $this->findById($id);

        return $this->categoriaGastoRepository->update($categoria, [
            'nombre' => $dto->nombre,
            'descripcion' => $dto->descripcion,
        ]);
    }

    public function changeStatus(int $id, bool $isActive): CategoriaGasto
    {
        $categoria = $this->findById($id);

        return $this->categoriaGastoRepository->changeStatus($categoria, $isActive);
    }
}