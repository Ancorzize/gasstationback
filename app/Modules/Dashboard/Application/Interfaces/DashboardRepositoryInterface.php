<?php

namespace App\Modules\Dashboard\Application\Interfaces;

use Illuminate\Support\Collection;

interface DashboardRepositoryInterface
{
    public function getWidgetsByRole(int $roleId): Collection;

    public function ventas(?string $fechaDesde, ?string $fechaHasta): float;

    public function compras(?string $fechaDesde, ?string $fechaHasta): float;

    public function gastos(?string $fechaDesde, ?string $fechaHasta): float;

    public function clientesTotales(): int;

    public function clientesNuevos(?string $fechaDesde, ?string $fechaHasta): int;

    public function productosActivos(): int;

    public function productosBajoStock(): int;

    public function cajasAbiertas(): int;

    public function turnosAbiertos(): int;

    public function ventasCredito(?string $fechaDesde, ?string $fechaHasta): float;

    public function abonos(?string $fechaDesde, ?string $fechaHasta): float;

    public function saldoCartera(): float;

    public function ventasUltimos30Dias(?string $fechaDesde, ?string $fechaHasta): array;

    public function ventasPorMedioPago(?string $fechaDesde, ?string $fechaHasta): array;

    public function topProductos(?string $fechaDesde, ?string $fechaHasta): array;

    public function ventasPorIslero(?string $fechaDesde, ?string $fechaHasta): array;

    public function galonesPorCombustible(?string $fechaDesde, ?string $fechaHasta): array;

    public function ventasPorDestinoRecaudo(?string $fechaDesde, ?string $fechaHasta): array;

    public function topClientes(?string $fechaDesde, ?string $fechaHasta): array;

    public function topProveedores(?string $fechaDesde, ?string $fechaHasta): array;

    public function recaudoPorMedioPago(?string $fechaDesde, ?string $fechaHasta): array;

    public function flujoCajaUltimos30Dias(?string $fechaDesde, ?string $fechaHasta): array;

    public function ingresosVsEgresos(?string $fechaDesde, ?string $fechaHasta): array;


    public function estadoCajas(): array;

    public function productosCriticos(): array;

    public function turnosAbiertosDetalle(): array;

    public function ultimasVentas(
        ?string $fechaDesde,
        ?string $fechaHasta,
        int $limit = 10
    ): array;

    public function ultimosGastos(
        ?string $fechaDesde,
        ?string $fechaHasta,
        int $limit = 10
    ): array;

    public function ultimasCompras(
        ?string $fechaDesde,
        ?string $fechaHasta,
        int $limit = 10
    ): array;

    public function ticketPromedio(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): float;

    public function comparativoVentasPeriodo(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): array;

    public function ventasPorHora(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): array;

    public function productosSinMovimiento(
    ): array;

    public function saldoPorCaja(
    ): array;

    public function recaudoPorCaja(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): array;

    public function clientesMayorDeuda(
    ): array;

    public function carteraVencida(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): float;

}