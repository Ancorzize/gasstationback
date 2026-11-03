<?php

namespace App\Modules\Compras\Application\Interfaces;

use App\Models\Compra;
use App\Models\Inventario;
use App\Models\PagoCompra;
use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\MovimientoInventario;
use App\Models\SaldoInicialProveedor;
use App\Models\AplicacionSaldoInicialProveedor;
use App\Models\AnticipoProveedor;
use App\Models\AplicacionAnticipoCompra;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CompraRepositoryInterface
{
    public function getAll(array $filters = []): Collection;

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

    public function getComprasPendientesByProveedor(int $proveedorId): Collection;
    
    public function getDeudaByProveedor(int $proveedorId): array;

    public function createSaldoInicial(array $data): SaldoInicialProveedor;

    public function updateSaldoInicial(SaldoInicialProveedor $saldoInicial, array $data): SaldoInicialProveedor;

    public function getSaldosInicialesPendientesByProveedor(int $proveedorId): Collection;

    public function existeDocumentoProveedor(int $proveedorId, string $numeroDocumento): bool;

    public function generarSiguienteConsecutivoSIP(): string;

    public function createAplicacionSaldoInicial(array $data): AplicacionSaldoInicialProveedor;

    public function getAplicacionesBySaldoInicial(int $saldoInicialId): Collection;

    public function createAnticipo(array $data): AnticipoProveedor;

    public function findAnticipoById(int $id): ?AnticipoProveedor;

    public function getAnticiposDisponiblesByProveedor(int $proveedorId): Collection;

    public function updateAnticipo(AnticipoProveedor $anticipo, array $data): AnticipoProveedor;

    public function generarSiguienteNumeroAnticipo(): string;

    public function createAplicacionAnticipo(array $data): AplicacionAnticipoCompra;

    public function getAplicacionesAnticipoByCompra(int $compraId): Collection;

    public function updateAplicacionAnticipo(AplicacionAnticipoCompra $app, array $data): AplicacionAnticipoCompra;
}
