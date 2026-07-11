<?php

namespace App\Modules\Compras\Application\Interfaces;

use App\Models\Compra;
use App\Models\Inventario;
use App\Models\PagoCompra;
use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\MovimientoInventario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CompraRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Compra;

    public function create(array $data): Compra;

    public function update(Compra $compra, array $data): Compra;

    public function deleteDetallesByCompra(int $compraId): void;

    public function createDetalle(array $data): void;

    public function findInventario(int $productoId, int $bodegaId): ?Inventario;

    public function createInventario(array $data): Inventario;

    public function incrementInventario(int $productoId, int $bodegaId, float $cantidad): void;

    public function createMovimientoInventario(array $data): MovimientoInventario;

    public function updateProductoPrecioCompra(int $productoId, float $precioCompra): void;

    public function createPago(array $data): PagoCompra;

    public function getPagosByCompra(int $compraId): Collection;

    public function createMovimientoCaja(array $data): MovimientoCaja;

    public function findCajaById(int $id): ?Caja;
    
}