<?php

namespace App\Modules\Inventarios\Application\Interfaces;

use App\Models\Bodega;
use App\Models\Producto;
use App\Models\Inventario;
use App\Models\MovimientoInventario;

interface InventarioImportRepositoryInterface
{
    public function findProductoByCodigo(string $codigo): ?Producto;

    public function findBodegaByCodigo(string $codigo): ?Bodega;

    public function findInventario(int $productoId, int $bodegaId): ?Inventario;

    public function createInventario(array $data): Inventario;

    public function incrementInventario(int $productoId, int $bodegaId, float $cantidad): void;

    public function createMovimiento(array $data): MovimientoInventario;
}