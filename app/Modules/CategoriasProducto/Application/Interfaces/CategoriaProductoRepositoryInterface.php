<?php

namespace App\Modules\CategoriasProducto\Application\Interfaces;

use App\Models\CategoriaProducto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CategoriaProductoRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?CategoriaProducto;

    public function create(array $data): CategoriaProducto;

    public function update(CategoriaProducto $categoria, array $data): CategoriaProducto;

    public function changeStatus(CategoriaProducto $categoria, bool $isActive): CategoriaProducto;

    public function delete(CategoriaProducto $categoria): void;
}