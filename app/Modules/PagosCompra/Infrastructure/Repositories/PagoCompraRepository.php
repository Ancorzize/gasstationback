<?php

namespace App\Modules\PagosCompra\Infrastructure\Repositories;

use App\Models\PagoCompra;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\PagosCompra\Application\Interfaces\PagoCompraRepositoryInterface;

class PagoCompraRepository implements PagoCompraRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = PagoCompra::query()
            ->with([
                'usuario',
                'compra.proveedor',
                'compra.bodega',
            ]);

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('observacion', 'like', "%{$search}%")
                    ->orWhere('metodo_pago', 'like', "%{$search}%")
                    ->orWhereHas('compra', function ($sub) use ($search) {
                        $sub->where('numero_documento', 'like', "%{$search}%");
                    })
                    ->orWhereHas('compra.proveedor', function ($sub) use ($search) {
                        $sub->where('nombre', 'like', "%{$search}%")
                            ->orWhere('nit', 'like', "%{$search}%");
                    })
                    ->orWhereHas('usuario', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($filters['compra_id']) && $filters['compra_id'] !== '') {
            $query->where('compra_id', $filters['compra_id']);
        }

        if (isset($filters['proveedor_id']) && $filters['proveedor_id'] !== '') {
            $query->whereHas('compra', function ($q) use ($filters) {
                $q->where('proveedor_id', $filters['proveedor_id']);
            });
        }

        if (!empty($filters['metodo_pago'])) {
            $query->where('metodo_pago', $filters['metodo_pago']);
        }

        if (isset($filters['user_id']) && $filters['user_id'] !== '') {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['fecha_desde'])) {
            $query->whereDate('fecha_pago', '>=', $filters['fecha_desde']);
        }

        if (!empty($filters['fecha_hasta'])) {
            $query->whereDate('fecha_pago', '<=', $filters['fecha_hasta']);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findById(int $id): ?PagoCompra
    {
        return PagoCompra::with([
            'usuario',
            'compra.proveedor',
            'compra.bodega',
            'compra.usuario',
            'compra.detalles.producto.marca',
            'compra.detalles.producto.categoriaProducto',
            'compra.detalles.producto.unidadMedida',
        ])->find($id);
    }
}