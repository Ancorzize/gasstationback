<?php

namespace App\Modules\CategoriasGasto\Infrastructure\Repositories;

use App\Models\CategoriaGasto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\CategoriasGasto\Application\Interfaces\CategoriaGastoRepositoryInterface;

class CategoriaGastoRepository implements CategoriaGastoRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = CategoriaGasto::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findById(int $id): ?CategoriaGasto
    {
        return CategoriaGasto::find($id);
    }

    public function create(array $data): CategoriaGasto
    {
        return CategoriaGasto::create($data);
    }

    public function update(CategoriaGasto $categoriaGasto, array $data): CategoriaGasto
    {
        $categoriaGasto->update($data);

        return $categoriaGasto->fresh();
    }

    public function changeStatus(CategoriaGasto $categoriaGasto, bool $isActive): CategoriaGasto
    {
        $categoriaGasto->update(['is_active' => $isActive]);

        return $categoriaGasto->fresh();
    }
}