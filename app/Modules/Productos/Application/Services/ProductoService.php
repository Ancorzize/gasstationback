<?php

namespace App\Modules\Productos\Application\Services;

use App\Models\Producto;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Productos\Application\DTOs\CreateProductoDTO;
use App\Modules\Productos\Application\DTOs\UpdateProductoDTO;
use App\Modules\Productos\Application\Interfaces\ProductoRepositoryInterface;

class ProductoService
{
    public function __construct(
        protected ProductoRepositoryInterface $productoRepository
    ) {}

    public function paginate(array $filters = [], int $perPage = 10)
    {
        return $this->productoRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): Producto
    {
        $producto = $this->productoRepository->findById($id);

        if (!$producto) {
            throw new HttpException(404, 'Producto no encontrado.');
        }

        return $producto;
    }

    public function create(CreateProductoDTO $dto): Producto
    {
        return $this->productoRepository->create([
            'codigo' => $dto->codigo,
            'nombre' => $dto->nombre,
            'descripcion' => $dto->descripcion,
            'marca_id' => $dto->marca_id,
            'categoria_producto_id' => $dto->categoria_producto_id,
            'unidad_medida_id' => $dto->unidad_medida_id,
            'precio_compra' => $dto->precio_compra,
            'precio_venta' => $dto->precio_venta,
            'permite_decimal' => $dto->permite_decimal,
            'is_active' => true,
        ]);
    }

    public function update(int $id, UpdateProductoDTO $dto): Producto
    {
        $producto = $this->findById($id);

        return $this->productoRepository->update($producto, [
            'codigo' => $dto->codigo,
            'nombre' => $dto->nombre,
            'descripcion' => $dto->descripcion,
            'marca_id' => $dto->marca_id,
            'categoria_producto_id' => $dto->categoria_producto_id,
            'unidad_medida_id' => $dto->unidad_medida_id,
            'precio_compra' => $dto->precio_compra,
            'precio_venta' => $dto->precio_venta,
            'permite_decimal' => $dto->permite_decimal,
        ]);
    }

    public function changeStatus(int $id, bool $isActive): Producto
    {
        $producto = $this->findById($id);

        return $this->productoRepository->changeStatus($producto, $isActive);
    }

    public function delete(int $id): void
    {
        $producto = $this->findById($id);

        $this->productoRepository->delete($producto);
    }
}