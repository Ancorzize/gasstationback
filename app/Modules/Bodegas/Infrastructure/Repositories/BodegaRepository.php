<?php

namespace App\Modules\Bodegas\Infrastructure\Repositories;

use App\Models\Bodega;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\Bodegas\Application\Interfaces\BodegaRepositoryInterface;

class BodegaRepository implements BodegaRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Bodega::query()->with('responsable');

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%")
                    ->orWhere('descripcion', 'like', "%{$search}%")
                    ->orWhere('direccion', 'like', "%{$search}%")
                    ->orWhere('telefono', 'like', "%{$search}%");
            });
        }

        if (isset($filters['responsable_id']) && $filters['responsable_id'] !== '') {
            $query->where('responsable_id', $filters['responsable_id']);
        }

        if (isset($filters['is_principal']) && $filters['is_principal'] !== '') {
            $query->where('is_principal', filter_var($filters['is_principal'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findById(int $id): ?Bodega
    {
        return Bodega::with('responsable')->find($id);
    }

    public function create(array $data): Bodega
    {
        return Bodega::create($data)->load('responsable');
    }

    public function update(Bodega $bodega, array $data): Bodega
    {
        $bodega->update($data);

        return $bodega->fresh()->load('responsable');
    }

    public function changeStatus(Bodega $bodega, bool $isActive): Bodega
    {
        $bodega->update(['is_active' => $isActive]);

        return $bodega->fresh()->load('responsable');
    }

    public function delete(Bodega $bodega): void
    {
        $bodega->delete();
    }

    public function clearPrincipalExcept(?int $exceptId = null): void
    {
        Bodega::query()
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where('is_principal', true)
            ->update(['is_principal' => false]);
    }
}