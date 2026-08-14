<?php

namespace App\Modules\Productos\Infrastructure\Repositories;

use App\Models\Producto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\Productos\Application\Interfaces\ProductoRepositoryInterface;

class ProductoRepository implements ProductoRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Producto::query()
            ->with(['marca', 'categoriaProducto', 'unidadMedida']);

        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(codigo) ILIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(nombre) ILIKE ?', ["%{$search}%"]);
            });
        }

        if (isset($filters['marca_id']) && $filters['marca_id'] !== '') {
            $query->where('marca_id', $filters['marca_id']);
        }

        if (isset($filters['categoria_producto_id']) && $filters['categoria_producto_id'] !== '') {
            $query->where('categoria_producto_id', $filters['categoria_producto_id']);
        }

        if (isset($filters['unidad_medida_id']) && $filters['unidad_medida_id'] !== '') {
            $query->where('unidad_medida_id', $filters['unidad_medida_id']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findById(int $id): ?Producto
    {
        return Producto::with(['marca', 'categoriaProducto', 'unidadMedida'])->find($id);
    }

    public function create(array $data): Producto
    {
        return Producto::create($data)->load(['marca', 'categoriaProducto', 'unidadMedida']);
    }

    public function update(Producto $producto, array $data): Producto
    {
        $producto->update($data);
        return $producto->fresh()->load(['marca', 'categoriaProducto', 'unidadMedida']);
    }

    public function changeStatus(Producto $producto, bool $isActive): Producto
    {
        $producto->update(['is_active' => $isActive]);
        return $producto->fresh()->load(['marca', 'categoriaProducto', 'unidadMedida']);
    }

    public function delete(Producto $producto): void
    {
        $producto->delete();
    }
}