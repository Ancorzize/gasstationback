<?php

namespace App\Modules\MovimientosInventario\Infrastructure\Repositories;

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\MovimientosInventario\Application\Interfaces\MovimientoInventarioRepositoryInterface;

class MovimientoInventarioRepository implements MovimientoInventarioRepositoryInterface
{
    public function getAll(array $filters = []): \Illuminate\Support\Collection
    {
        $query = MovimientoInventario::query()
            ->with([
                'producto',
                'bodegaOrigen',
                'bodegaDestino',
                'usuario'
            ]);

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->whereHas('producto', function ($sub) use ($search) {
                    $sub->where('codigo', 'like', "%{$search}%")
                        ->orWhere('nombre', 'like', "%{$search}%");
                })
                ->orWhereHas('bodegaOrigen', function ($sub) use ($search) {
                    $sub->where('nombre', 'like', "%{$search}%")
                        ->orWhere('codigo', 'like', "%{$search}%");
                })
                ->orWhereHas('bodegaDestino', function ($sub) use ($search) {
                    $sub->where('nombre', 'like', "%{$search}%")
                        ->orWhere('codigo', 'like', "%{$search}%");
                })
                ->orWhere(
                    'observacion',
                    'like',
                    "%{$search}%"
                );
            });
        }

        if (!empty($filters['tipo_movimiento'])) {
            $query->whereIn(
                'tipo_movimiento',
                $filters['tipo_movimiento']
            );
        }

        if (
            isset($filters['producto_id']) &&
            $filters['producto_id'] !== ''
        ) {
            $query->where(
                'producto_id',
                $filters['producto_id']
            );
        }

        if (
            isset($filters['bodega_origen_id']) &&
            $filters['bodega_origen_id'] !== ''
        ) {
            $query->where(
                'bodega_origen_id',
                $filters['bodega_origen_id']
            );
        }

        if (
            isset($filters['bodega_destino_id']) &&
            $filters['bodega_destino_id'] !== ''
        ) {
            $query->where(
                'bodega_destino_id',
                $filters['bodega_destino_id']
            );
        }

        if (
            isset($filters['user_id']) &&
            $filters['user_id'] !== ''
        ) {
            $query->where(
                'user_id',
                $filters['user_id']
            );
        }

        if (!empty($filters['fecha_desde'])) {
            $query->whereDate(
                'created_at',
                '>=',
                $filters['fecha_desde']
            );
        }

        if (!empty($filters['fecha_hasta'])) {
            $query->whereDate(
                'created_at',
                '<=',
                $filters['fecha_hasta']
            );
        }

        return $query
            ->orderByDesc('id')
            ->get();
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

    public function decrementInventario(int $productoId, int $bodegaId, float $cantidad): void
    {
        Inventario::query()
            ->where('producto_id', $productoId)
            ->where('bodega_id', $bodegaId)
            ->decrement('cantidad', $cantidad);
    }

    public function createMovimiento(array $data): MovimientoInventario
    {
        return MovimientoInventario::create($data)
            ->load(['producto', 'bodegaOrigen', 'bodegaDestino', 'usuario']);
    }

    public function findInventarioForUpdate(
        int $productoId,
        int $bodegaId
    ): ?Inventario {
        return Inventario::query()
            ->where('producto_id', $productoId)
            ->where('bodega_id', $bodegaId)
            ->lockForUpdate()
            ->first();
    }
}