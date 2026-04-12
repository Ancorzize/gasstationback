<?php

namespace App\Modules\Compras\Infrastructure\Repositories;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\Inventario;
use App\Models\PagoCompra;
use App\Models\DetalleCompra;
use App\Models\MovimientoInventario;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\Compras\Application\Interfaces\CompraRepositoryInterface;

class CompraRepository implements CompraRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Compra::query()
            ->with(['proveedor', 'bodega', 'usuario', 'detalles.producto']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('numero_documento', 'like', "%{$search}%")
                    ->orWhereHas('proveedor', function ($sub) use ($search) {
                        $sub->where('nombre', 'like', "%{$search}%")
                            ->orWhere('nit', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($filters['proveedor_id']) && $filters['proveedor_id'] !== '') {
            $query->where('proveedor_id', $filters['proveedor_id']);
        }

        if (isset($filters['bodega_id']) && $filters['bodega_id'] !== '') {
            $query->where('bodega_id', $filters['bodega_id']);
        }

        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (!empty($filters['estado_pago'])) {
            $query->where('estado_pago', $filters['estado_pago']);
        }

        if (!empty($filters['tipo_pago'])) {
            $query->where('tipo_pago', $filters['tipo_pago']);
        }

        if (!empty($filters['fecha_desde'])) {
            $query->whereDate('fecha_compra', '>=', $filters['fecha_desde']);
        }

        if (!empty($filters['fecha_hasta'])) {
            $query->whereDate('fecha_compra', '<=', $filters['fecha_hasta']);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findById(int $id): ?Compra
    {
        return Compra::with([
            'proveedor',
            'bodega',
            'usuario',
            'detalles.producto.marca',
            'detalles.producto.categoriaProducto',
            'detalles.producto.unidadMedida',
            'pagos.usuario',
        ])->find($id);
    }

    public function create(array $data): Compra
    {
        return Compra::create($data);
    }

    public function update(Compra $compra, array $data): Compra
    {
        $compra->update($data);

        return $compra->fresh();
    }

    public function deleteDetallesByCompra(int $compraId): void
    {
        DetalleCompra::query()
            ->where('compra_id', $compraId)
            ->delete();
    }

    public function createDetalle(array $data): void
    {
        DetalleCompra::create($data);
    }

    public function findInventario(int $productoId, int $bodegaId): ?Inventario
    {
        return Inventario::query()
            ->where('producto_id', $productoId)
            ->where('bodega_id', $bodegaId)
            ->first();
    }

    public function createInventario(array $data): Inventario
    {
        return Inventario::create($data);
    }

    public function incrementInventario(int $productoId, int $bodegaId, float $cantidad): void
    {
        Inventario::query()
            ->where('producto_id', $productoId)
            ->where('bodega_id', $bodegaId)
            ->increment('cantidad', $cantidad);
    }

    public function createMovimientoInventario(array $data): MovimientoInventario
    {
        return MovimientoInventario::create($data);
    }

    public function updateProductoPrecioCompra(int $productoId, float $precioCompra): void
    {
        Producto::query()
            ->where('id', $productoId)
            ->update(['precio_compra' => $precioCompra]);
    }

    public function createPago(array $data): PagoCompra
    {
        return PagoCompra::create($data)->load('usuario');
    }

    public function getPagosByCompra(int $compraId): Collection
    {
        return PagoCompra::query()
            ->with('usuario')
            ->where('compra_id', $compraId)
            ->orderByDesc('id')
            ->get();
    }
}