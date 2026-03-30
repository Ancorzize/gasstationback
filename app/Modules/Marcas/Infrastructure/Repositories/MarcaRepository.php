<?php

namespace App\Modules\Marcas\Infrastructure\Repositories;

use App\Models\Marca;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\Marcas\Application\Interfaces\MarcaRepositoryInterface;

class MarcaRepository implements MarcaRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Marca::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where('nombre', 'like', "%{$search}%");
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findById(int $id): ?Marca
    {
        return Marca::find($id);
    }

    public function create(array $data): Marca
    {
        return Marca::create($data);
    }

    public function update(Marca $marca, array $data): Marca
    {
        $marca->update($data);
        return $marca->fresh();
    }

    public function changeStatus(Marca $marca, bool $isActive): Marca
    {
        $marca->update(['is_active' => $isActive]);
        return $marca->fresh();
    }

    public function delete(Marca $marca): void
    {
        $marca->delete();
    }
}