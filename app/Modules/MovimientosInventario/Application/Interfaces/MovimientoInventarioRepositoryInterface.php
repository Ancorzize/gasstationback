<?php

namespace App\Modules\MovimientosInventario\Application\Interfaces;

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MovimientoInventarioRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findInventario(int $productoId, int $bodegaId): ?Inventario;

    public function createInventario(array $data): Inventario;

    public function incrementInventario(int $productoId, int $bodegaId, float $cantidad): void;

    public function decrementInventario(int $productoId, int $bodegaId, float $cantidad): void;

    public function createMovimiento(array $data): MovimientoInventario;

    public function findInventarioForUpdate(int $productoId, int $bodegaId): ?Inventario;
}