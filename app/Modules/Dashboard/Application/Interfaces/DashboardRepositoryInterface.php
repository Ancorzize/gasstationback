<?php

namespace App\Modules\Dashboard\Application\Interfaces;

use Illuminate\Support\Collection;

interface DashboardRepositoryInterface
{
    public function getWidgetsByRole(int $roleId): Collection;

    public function ventasHoy(?string $fechaDesde, ?string $fechaHasta): float;

    public function comprasHoy(?string $fechaDesde, ?string $fechaHasta): float;

    public function gastosHoy(?string $fechaDesde, ?string $fechaHasta): float;

    public function ventasMes(?string $fechaDesde, ?string $fechaHasta): float;

    public function comprasMes(?string $fechaDesde, ?string $fechaHasta): float;

    public function gastosMes(?string $fechaDesde, ?string $fechaHasta): float;

    public function clientesTotales(?string $fechaDesde, ?string $fechaHasta): int;

    public function clientesNuevosHoy(?string $fechaDesde, ?string $fechaHasta): int;

    public function productosActivos(): int;

    public function productosBajoStock(): int;

    public function cajasAbiertas(): int;

    public function turnosAbiertos(): int;

    public function ventasCreditoHoy(?string $fechaDesde, ?string $fechaHasta): float;

    public function abonosHoy(?string $fechaDesde, ?string $fechaHasta): float;

    public function saldoCartera(?string $fechaDesde, ?string $fechaHasta): float;

    public function inventarioValorizado(): float;


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
}