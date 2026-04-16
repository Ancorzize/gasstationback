<?php

namespace App\Modules\CategoriasGasto\Application\Interfaces;

use App\Models\CategoriaGasto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CategoriaGastoRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?CategoriaGasto;

    public function create(array $data): CategoriaGasto;

    public function update(CategoriaGasto $categoriaGasto, array $data): CategoriaGasto;

    public function changeStatus(CategoriaGasto $categoriaGasto, bool $isActive): CategoriaGasto;
}