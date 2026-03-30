<?php

namespace App\Modules\CategoriasProducto\Infrastructure\Repositories;

use App\Models\CategoriaProducto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\CategoriasProducto\Application\Interfaces\CategoriaProductoRepositoryInterface;

class CategoriaProductoRepository implements CategoriaProductoRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = CategoriaProducto::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('nombre', 'like', "%{$search}%");
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findById(int $id): ?CategoriaProducto
    {
        return CategoriaProducto::find($id);
    }

    public function create(array $data): CategoriaProducto
    {
        return CategoriaProducto::create($data);
    }

    public function update(CategoriaProducto $categoria, array $data): CategoriaProducto
    {
        $categoria->update($data);
        return $categoria->fresh();
    }

    public function changeStatus(CategoriaProducto $categoria, bool $isActive): CategoriaProducto
    {
        $categoria->update(['is_active' => $isActive]);
        return $categoria->fresh();
    }

    public function delete(CategoriaProducto $categoria): void
    {
       $categoria->delete();
    }
}