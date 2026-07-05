<?php

namespace App\Modules\CategoriasProducto\Application\Services;

use App\Models\CategoriaProducto;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\CategoriasProducto\Application\DTOs\CreateCategoriaProductoDTO;
use App\Modules\CategoriasProducto\Application\DTOs\UpdateCategoriaProductoDTO;
use App\Modules\CategoriasProducto\Application\Interfaces\CategoriaProductoRepositoryInterface;

class CategoriaProductoService
{
    public function __construct(
        protected CategoriaProductoRepositoryInterface $categoriaProductoRepository
    ) {}

    public function paginate(array $filters = [], int $perPage = 10)
    {
        return $this->categoriaProductoRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): CategoriaProducto
    {
        $categoria = $this->categoriaProductoRepository->findById($id);

        if (!$categoria) {
            throw new HttpException(404, 'Categoría no encontrada.');
        }

        return $categoria;
    }

    public function create(CreateCategoriaProductoDTO $dto): CategoriaProducto
    {
        return $this->categoriaProductoRepository->create([
            'nombre' => $dto->nombre,
            'descripcion' => $dto->descripcion,
            'is_active' => true,
            'destino_recaudo_id' => $dto->destino_recaudo_id,
        ]);
    }

    public function update(int $id, UpdateCategoriaProductoDTO $dto): CategoriaProducto
    {
        $categoria = $this->findById($id);

        return $this->categoriaProductoRepository->update($categoria, [
            'nombre' => $dto->nombre,
            'descripcion' => $dto->descripcion,
            'destino_recaudo_id' => $dto->destino_recaudo_id,
        ]);
    }

    public function changeStatus(int $id, bool $isActive): CategoriaProducto
    {
        $categoria = $this->findById($id);

        return $this->categoriaProductoRepository->changeStatus($categoria, $isActive);
    }

    public function delete(int $id): void
    {
        $categoria = $this->findById($id);

        $this->categoriaProductoRepository->delete($categoria);
    }
}