<?php

namespace App\Modules\Ventas\Application\Interfaces;

use App\Models\Caja;
use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Inventario;
use App\Models\PagoVenta;
use App\Models\MovimientoCaja;
use App\Models\MovimientoCartera;
use App\Models\MovimientoInventario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\TurnoIslero;
use App\Models\Manguera;
use App\Models\LecturaManguera;
use App\Models\User;

interface VentaRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function findById(int $id): ?Venta;

    public function createVenta(array $data): Venta;

    public function createDetalle(array $data): void;

    public function createPago(array $data): PagoVenta;

    public function findClienteById(int $id): ?Cliente;

    public function updateCliente(Cliente $cliente, array $data): Cliente;

    public function findProductoById(int $id): ?Producto;

    public function findInventario(int $productoId, int $bodegaId): ?Inventario;

    public function decrementInventario(int $productoId, int $bodegaId, float $cantidad): void;

    public function createMovimientoInventario(array $data): MovimientoInventario;

    public function createMovimientoCaja(array $data): MovimientoCaja;

    public function createMovimientoCartera(array $data): MovimientoCartera;

    public function nextNumeroFactura(): string;

    public function updateVenta(Venta $venta, array $data): Venta;

    public function incrementInventario(int $productoId, int $bodegaId, float $cantidad): void;

    public function getTurnoAbiertoByUser(int $userId): ?TurnoIslero;

    public function findMangueraById(int $id): ?Manguera;

    public function getLecturaAbiertaByTurnoAndManguera(int $turnoId, int $mangueraId): ?LecturaManguera;

    public function findUserById(int $id): ?User;

    public function getCajaAbiertaByTipoAndDestino( string $tipoCaja, int $destinoRecaudoId): ?Caja;
}