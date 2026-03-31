<?php

namespace App\Modules\Servicios\Infrastructure\Repositories;

use App\Models\Servicio;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\Servicios\Application\Interfaces\ServicioRepositoryInterface;

class ServicioRepository implements ServicioRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Servicio::query()->with('unidadMedida');

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findById(int $id): ?Servicio
    {
        return Servicio::with('unidadMedida')->find($id);
    }

    public function create(array $data): Servicio
    {
        return Servicio::create($data)->load('unidadMedida');
    }

    public function update(Servicio $servicio, array $data): Servicio
    {
        $servicio->update($data);
        return $servicio->fresh()->load('unidadMedida');
    }

    public function changeStatus(Servicio $servicio, bool $isActive): Servicio
    {
        $servicio->update(['is_active' => $isActive]);
        return $servicio->fresh()->load('unidadMedida');
    }

    public function delete(Servicio $servicio): void
    {
        $servicio->delete();
    }
}