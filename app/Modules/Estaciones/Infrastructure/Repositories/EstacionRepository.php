<?php

namespace App\Modules\Estaciones\Infrastructure\Repositories;

use App\Models\Estacion;
use App\Modules\Estaciones\Application\Interfaces\EstacionRepositoryInterface;

class EstacionRepository implements EstacionRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10)
    {
        $query = Estacion::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%")
                    ->orWhere('direccion', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findById(int $id): ?Estacion
    {
        return Estacion::with('bombas.mangueras.producto')->find($id);
    }

    public function create(array $data): Estacion
    {
        return Estacion::create($data);
    }

    public function update(Estacion $estacion, array $data): Estacion
    {
        $estacion->update($data);

        return $estacion->fresh();
    }

    public function changeStatus(Estacion $estacion, bool $isActive): Estacion
    {
        $estacion->update(['is_active' => $isActive]);

        return $estacion->fresh();
    }
}