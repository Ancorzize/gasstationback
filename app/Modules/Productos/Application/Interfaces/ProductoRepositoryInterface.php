<?php

namespace App\Modules\Productos\Application\Interfaces;

use App\Models\Producto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductoRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10, ?int $bodegaId = null);

    public function findById(int $id): ?Producto;

    public function create(array $data): Producto;

    public function update(Producto $producto, array $data): Producto;

    public function changeStatus(Producto $producto, bool $isActive): Producto;

    public function delete(Producto $producto): void;
}