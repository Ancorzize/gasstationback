<?php

namespace App\Modules\UnidadesMedida\Infrastructure\Repositories;

use App\Models\UnidadMedida;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\UnidadesMedida\Application\Interfaces\UnidadMedidaRepositoryInterface;

class UnidadMedidaRepository implements UnidadMedidaRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = UnidadMedida::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('abreviatura', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findById(int $id): ?UnidadMedida
    {
        return UnidadMedida::find($id);
    }

    public function create(array $data): UnidadMedida
    {
        return UnidadMedida::create($data);
    }

    public function update(UnidadMedida $unidadMedida, array $data): UnidadMedida
    {
        $unidadMedida->update($data);
        return $unidadMedida->fresh();
    }

    public function changeStatus(UnidadMedida $unidadMedida, bool $isActive): UnidadMedida
    {
        $unidadMedida->update(['is_active' => $isActive]);
        return $unidadMedida->fresh();
    }

    public function delete(UnidadMedida $unidadMedida): void
    {
        $unidadMedida->delete($unidadMedida);
    }
}