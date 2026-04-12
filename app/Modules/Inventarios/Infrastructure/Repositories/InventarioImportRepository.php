<?php

namespace App\Modules\Inventarios\Infrastructure\Repositories;

use App\Models\Bodega;
use App\Models\Producto;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Modules\Inventarios\Application\Interfaces\InventarioImportRepositoryInterface;

class InventarioImportRepository implements InventarioImportRepositoryInterface
{
    public function findProductoByCodigo(string $codigo): ?Producto
    {
        return Producto::query()
            ->where('codigo', $codigo)
            ->first();
    }

    public function findBodegaByCodigo(string $codigo): ?Bodega
    {
        return Bodega::query()
            ->where('codigo', $codigo)
            ->first();
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

    public function createMovimiento(array $data): MovimientoInventario
    {
        return MovimientoInventario::create($data);
    }
}