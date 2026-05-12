<?php

namespace App\Modules\PreciosCombustible\Infrastructure\Repositories;

use App\Models\PrecioCombustible;
use App\Modules\PreciosCombustible\Application\Interfaces\PrecioCombustibleRepositoryInterface;

class PrecioCombustibleRepository implements PrecioCombustibleRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10)
    {
        $query = PrecioCombustible::query()
            ->with(['producto.marca', 'producto.categoriaProducto', 'producto.unidadMedida']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->whereHas('producto', function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        if (isset($filters['producto_id']) && $filters['producto_id'] !== '') {
            $query->where('producto_id', $filters['producto_id']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findById(int $id): ?PrecioCombustible
    {
        return PrecioCombustible::with([
            'producto.marca',
            'producto.categoriaProducto',
            'producto.unidadMedida',
        ])->find($id);
    }

    public function create(array $data): PrecioCombustible
    {
        return PrecioCombustible::create($data)->load([
            'producto.marca',
            'producto.categoriaProducto',
            'producto.unidadMedida',
        ]);
    }

    public function cerrarPreciosActivosProducto(int $productoId): void
    {
        PrecioCombustible::query()
            ->where('producto_id', $productoId)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'fecha_fin' => now(),
            ]);
    }

    public function changeStatus(PrecioCombustible $precio, bool $isActive): PrecioCombustible
    {
        $precio->update([
            'is_active' => $isActive,
            'fecha_fin' => $isActive ? null : now(),
        ]);

        return $precio->fresh()->load([
            'producto.marca',
            'producto.categoriaProducto',
            'producto.unidadMedida',
        ]);
    }
}