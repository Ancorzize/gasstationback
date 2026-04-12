<?php

namespace App\Modules\Inventarios\Infrastructure\Repositories;

use App\Models\Inventario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\Inventarios\Application\Interfaces\InventarioRepositoryInterface;

class InventarioRepository implements InventarioRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Inventario::query()
            ->with(['producto.marca', 'producto.categoriaProducto', 'producto.unidadMedida', 'bodega']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->whereHas('producto', function ($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                    ->orWhere('nombre', 'like', "%{$search}%");
            });
        }

        if (isset($filters['producto_id']) && $filters['producto_id'] !== '') {
            $query->where('producto_id', $filters['producto_id']);
        }

        if (isset($filters['bodega_id']) && $filters['bodega_id'] !== '') {
            $query->where('bodega_id', $filters['bodega_id']);
        }

        if (isset($filters['marca_id']) && $filters['marca_id'] !== '') {
            $query->whereHas('producto', function ($q) use ($filters) {
                $q->where('marca_id', $filters['marca_id']);
            });
        }

        if (isset($filters['categoria_producto_id']) && $filters['categoria_producto_id'] !== '') {
            $query->whereHas('producto', function ($q) use ($filters) {
                $q->where('categoria_producto_id', $filters['categoria_producto_id']);
            });
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findByProductoAndBodega(int $productoId, int $bodegaId): ?Inventario
    {
        return Inventario::query()
            ->where('producto_id', $productoId)
            ->where('bodega_id', $bodegaId)
            ->first();
    }

    public function findById(int $id): ?Inventario
    {
        return Inventario::with(['producto', 'bodega'])->find($id);
    }

    public function create(array $data): Inventario
    {
        return Inventario::create($data);
    }

    public function update(Inventario $inventario, array $data): Inventario
    {
        $inventario->update($data);

        return $inventario->fresh()->load(['producto', 'bodega']);
    }

    public function getByBodega(int $bodegaId)
    {
        return Inventario::query()
            ->with(['producto.marca', 'producto.categoriaProducto', 'producto.unidadMedida', 'bodega'])
            ->where('bodega_id', $bodegaId)
            ->orderByDesc('id')
            ->get();
    }

    public function getResumen(array $filters = [])
    {
        $query = Inventario::query()
            ->selectRaw('producto_id, SUM(cantidad) as cantidad_total')
            ->with(['producto.marca', 'producto.categoriaProducto', 'producto.unidadMedida'])
            ->groupBy('producto_id');

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->whereHas('producto', function ($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                    ->orWhere('nombre', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    public function getByBodegaAndUser(int $bodegaId)
    {
        return Inventario::query()
            ->with(['producto.marca', 'producto.categoriaProducto', 'producto.unidadMedida', 'bodega'])
            ->where('bodega_id', $bodegaId)
            ->orderByDesc('id')
            ->get();
    }
}