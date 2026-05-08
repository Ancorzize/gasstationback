<?php

namespace App\Modules\Bombas\Infrastructure\Repositories;

use App\Models\Bomba;
use App\Modules\Bombas\Application\Interfaces\BombaRepositoryInterface;

class BombaRepository implements BombaRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10)
    {
        $query = Bomba::query()
            ->with(['estacion']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%")
                    ->orWhereHas('estacion', function ($sub) use ($search) {
                        $sub->where('nombre', 'like', "%{$search}%")
                            ->orWhere('codigo', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($filters['estacion_id']) && $filters['estacion_id'] !== '') {
            $query->where('estacion_id', $filters['estacion_id']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findById(int $id): ?Bomba
    {
        return Bomba::with(['estacion', 'mangueras.producto'])->find($id);
    }

    public function create(array $data): Bomba
    {
        return Bomba::create($data)->load(['estacion']);
    }

    public function update(Bomba $bomba, array $data): Bomba
    {
        $bomba->update($data);

        return $bomba->fresh()->load(['estacion']);
    }

    public function changeStatus(Bomba $bomba, bool $isActive): Bomba
    {
        $bomba->update(['is_active' => $isActive]);

        return $bomba->fresh()->load(['estacion']);
    }
}