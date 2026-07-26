<?php

namespace App\Modules\Dashboard\Application\Services;

use App\Modules\Dashboard\Application\Interfaces\DashboardRepositoryInterface;


class DashboardIndicatorService
{
    private ?string $fechaDesde = null;

    private ?string $fechaHasta = null;

    public function __construct(
        protected DashboardRepositoryInterface $dashboardRepository
    ) {}

    public function setPeriodo(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): void {

        $this->fechaDesde = $fechaDesde;
        $this->fechaHasta = $fechaHasta;
    }

    public function getIndicator(string $codigo): mixed
    {
        return match ($codigo) {

            'ventas' => $this->ventas(),

            'compras' => $this->compras(),

            'gastos' => $this->gastos(),

            'clientes_totales' => $this->clientesTotales(),

            'clientes_nuevos' => $this->clientesNuevos(),

            'productos_activos' => $this->productosActivos(),

            'productos_bajo_stock' => $this->productosBajoStock(),

            'cajas_abiertas' => $this->cajasAbiertas(),

            'turnos_abiertos' => $this->turnosAbiertos(),

            'ventas_credito' => $this->ventasCredito(),

            'abonos' => $this->abonos(),

            'saldo_cartera' => $this->saldoCartera(),

            'ventas_30_dias'=>$this->ventas30Dias(),

            'ventas_medio_pago'=>$this->ventasMedioPago(),

            'top_productos' => $this->topProductos(),

            'ventas_por_islero' => $this->ventasPorIslero(),

            'galones_combustible'=>$this->galonesCombustible(),

            'estado_cajas' => $this->estadoCajas(),

            'productos_criticos' => $this->productosCriticos(),

            'turnos_abiertos_detalle' => $this->turnosAbiertosDetalle(),

            'ultimas_ventas' => $this->ultimasVentas(),

            'ultimos_gastos' => $this->ultimosGastos(),

            'ultimas_compras' => $this->ultimasCompras(),

            'ventas_destino_recaudo' => $this->ventasDestinoRecaudo(),

            'top_clientes' => $this->topClientes(),

            'top_proveedores' => $this->topProveedores(),

            'recaudo_medio_pago'=>$this->recaudoMedioPago(),

            'flujo_caja'=>$this->flujoCaja(),

            'ingresos_egresos'=>$this->ingresosEgresos(),

            'ticket_promedio' => $this->ticketPromedio(),

            'comparativo_ventas' =>  $this->comparativoVentasPeriodo(),

            'ventas_por_hora' =>$this->ventasPorHora(),

            'cartera_vencida' => $this->carteraVencida(),

            'productos_sin_movimiento' => $this->productosSinMovimiento(),

            'saldo_por_caja' => $this->saldoPorCaja(),

            'recaudo_por_caja' => $this->recaudoPorCaja(),

            'clientes_mayor_deuda' => $this->clientesMayorDeuda(),

            default => null,
        };
    }

    private function ventas(): array
    {
        return [

            'valor' => $this->dashboardRepository
                ->ventas($this->fechaDesde, $this->fechaHasta)

        ];
    }

    private function compras(): array
    {
        return [

            'valor' => $this->dashboardRepository
                ->compras($this->fechaDesde, $this->fechaHasta)

        ];
    }

    private function gastos(): array
    {
        return [

            'valor' => $this->dashboardRepository
                ->gastos($this->fechaDesde, $this->fechaHasta)

        ];
    }

    private function clientesTotales(): array
    {
        return [
            'valor'=>$this->dashboardRepository
                ->clientesTotales()
        ];
    }

    private function clientesNuevos(): array
    {
        return [
            'valor'=>$this->dashboardRepository
                ->clientesNuevos($this->fechaDesde, $this->fechaHasta)
        ];
    }

    private function productosActivos(): array
    {
        return [
            'valor'=>$this->dashboardRepository
                ->productosActivos()
        ];
    }

    private function productosBajoStock(): array
    {
        return [
            'valor'=>$this->dashboardRepository
                ->productosBajoStock()
        ];
    }

    private function cajasAbiertas(): array
    {
        return [
            'valor'=>$this->dashboardRepository
                ->cajasAbiertas()
        ];
    }

    private function turnosAbiertos(): array
    {
        return [
            'valor'=>$this->dashboardRepository
                ->turnosAbiertos()
        ];
    }

    private function ventasCredito(): array
    {
        return [
            'valor'=>$this->dashboardRepository
                ->ventasCredito($this->fechaDesde, $this->fechaHasta)
        ];
    }

    private function abonos(): array
    {
        return [
            'valor'=>$this->dashboardRepository
                ->abonos($this->fechaDesde, $this->fechaHasta)
        ];
    }

    private function saldoCartera(): array
    {
        return [
            'valor'=>$this->dashboardRepository->saldoCartera()
        ];
    }

    private function ventas30Dias(): array
    {
        $datos = $this->dashboardRepository
        ->ventasUltimos30Dias(
            $this->fechaDesde,
            $this->fechaHasta
        );

        return [

            'labels' => collect($datos)
                ->pluck('fecha'),

            'series' => collect($datos)
                ->pluck('valor'),

            'items' => $datos

        ];
    }
    
    private function ventasMedioPago(): array
    {
        $datos=
            $this->dashboardRepository
                ->ventasPorMedioPago($this->fechaDesde, $this->fechaHasta);

        return [

            'labels'=>collect($datos)
                ->pluck('nombre'),

            'series'=>collect($datos)
                ->pluck('valor'),

            'items' => $datos

        ];
    }

    private function topProductos(): array
    {
        $datos = $this->dashboardRepository
            ->topProductos($this->fechaDesde, $this->fechaHasta);

        return [

            'labels' => collect($datos)
                ->pluck('nombre'),

            'series' => collect($datos)
                ->pluck('valor'),

            'items' => $datos,

        ];
    }

    private function ventasPorIslero(): array
    {
        $datos = $this->dashboardRepository
            ->ventasPorIslero($this->fechaDesde, $this->fechaHasta);

        return [

            'labels' => collect($datos)
                ->pluck('nombre'),

            'series' => collect($datos)
                ->pluck('total'),

            'items' => $datos,

        ];
    }

    private function galonesCombustible(): array
    {
        $datos =
            $this->dashboardRepository
                ->galonesPorCombustible($this->fechaDesde, $this->fechaHasta);

        return [

            'labels'=>collect($datos)
                ->pluck('nombre'),

            'series'=>collect($datos)
                ->pluck('galones'),

            'items'=>$datos,

        ];
    }

    private function estadoCajas(): array
    {
        return [

            'items' => $this->dashboardRepository
                ->estadoCajas()

        ];
    }

    private function productosCriticos(): array
    {
        return [

            'items' =>

            $this->dashboardRepository
                ->productosCriticos()

        ];
    }

    private function turnosAbiertosDetalle(): array
    {
        return [

            'items' =>

            $this->dashboardRepository
                ->turnosAbiertosDetalle()

        ];
    }

    private function ultimasVentas(): array
    {
        return [

            'items' =>

            $this->dashboardRepository
                ->ultimasVentas($this->fechaDesde, $this->fechaHasta)

        ];
    }

    private function ultimosGastos(): array
    {
        return [

            'items' =>

            $this->dashboardRepository
                ->ultimosGastos($this->fechaDesde, $this->fechaHasta)

        ];
    }

    private function ultimasCompras(): array
    {
        return [

            'items' =>

            $this->dashboardRepository
                ->ultimasCompras($this->fechaDesde, $this->fechaHasta)

        ];
    }

    private function ventasDestinoRecaudo(): array
    {
        $datos = $this->dashboardRepository
            ->ventasPorDestinoRecaudo($this->fechaDesde, $this->fechaHasta);

        return [

            'labels' => collect($datos)
                ->pluck('nombre'),

            'series' => collect($datos)
                ->pluck('total'),

            'items' => $datos,

        ];
    }

    private function topClientes(): array
    {
        $datos = $this->dashboardRepository
            ->topClientes($this->fechaDesde, $this->fechaHasta);

        return [

            'labels' => collect($datos)
                ->pluck('nombre'),

            'series' => collect($datos)
                ->pluck('total'),

            'items' => $datos,

        ];
    }
    private function topProveedores(): array
    {
        $datos = $this->dashboardRepository
            ->topProveedores($this->fechaDesde, $this->fechaHasta);

        return [

            'labels' => collect($datos)
                ->pluck('nombre'),

            'series' => collect($datos)
                ->pluck('total'),

            'items' => $datos,

        ];
    }

    private function recaudoMedioPago(): array
    {
        $datos =
            $this->dashboardRepository
                ->recaudoPorMedioPago($this->fechaDesde, $this->fechaHasta);

        return [

            'labels'=>collect($datos)
                ->pluck('nombre'),

            'series'=>collect($datos)
                ->pluck('total'),

            'items'=>$datos,

        ];
    }

    private function flujoCaja(): array
    {
        $datos =
            $this->dashboardRepository
                ->flujoCajaUltimos30Dias(
                    $this->fechaDesde,
                    $this->fechaHasta
                );

        return [

            'labels' => collect($datos)
                ->pluck('fecha'),

            'series' => [

                [
                    'name' => 'Ingresos',
                    'data' => collect($datos)
                        ->pluck('ingresos'),
                ],

                [
                    'name' => 'Egresos',
                    'data' => collect($datos)
                        ->pluck('egresos'),
                ],

            ],

            'items' => $datos,

        ];
    }

    private function ingresosEgresos(): array
    {
        $datos =
            $this->dashboardRepository
                ->ingresosVsEgresos($this->fechaDesde, $this->fechaHasta);

        return [

            'labels'=>collect($datos)
                ->pluck('nombre'),

            'series'=>collect($datos)
                ->pluck('total'),

            'items'=>$datos,

        ];
    }

    private function ticketPromedio(): array
    {
        return [

            'valor' => $this->dashboardRepository
                ->ticketPromedio(
                    $this->fechaDesde,
                    $this->fechaHasta
                ),

            'tipo' => 'currency',

        ];
    }

    private function comparativoVentasPeriodo(): array
    {
        return $this->dashboardRepository
            ->comparativoVentasPeriodo(
                $this->fechaDesde,
                $this->fechaHasta
            );
    }

    private function ventasPorHora(): array
    {
        $datos = $this->dashboardRepository->ventasPorHora($this->fechaDesde,$this->fechaHasta);

        return [
            'labels' => collect($datos)->pluck('hora')->map(fn($h) => "{$h}:00"),
            'series' => collect($datos)->pluck('total'),
            'items' => $datos
        ];
    }

    private function carteraVencida(): array
    {
        return [

            'valor' => $this->dashboardRepository
                ->carteraVencida(
                    $this->fechaDesde,
                    $this->fechaHasta
                ),

            'formato' => 'currency',

        ];
    }


    private function productosSinMovimiento(): array
    {
        return [

            'items' => $this->dashboardRepository
                ->productosSinMovimiento(),

        ];
    }

    private function saldoPorCaja(): array
    {
        $datos = $this->dashboardRepository
            ->saldoPorCaja();

        return [

            'labels' => collect($datos)
                ->pluck('nombre'),

            'series' => collect($datos)
                ->pluck('saldo'),

            'items' => $datos,

        ];
    }

    private function recaudoPorCaja(): array
    {
        $datos = $this->dashboardRepository
            ->recaudoPorCaja(
                $this->fechaDesde,
                $this->fechaHasta
            );

        return [

            'labels' => collect($datos)
                ->pluck('nombre'),

            'series' => collect($datos)
                ->pluck('total'),

            'items' => $datos,

        ];
    }

    private function clientesMayorDeuda(): array
    {
        $datos = $this->dashboardRepository
            ->clientesMayorDeuda();

        return [

            'labels' => collect($datos)
                ->pluck('nombre'),

            'series' => collect($datos)
                ->pluck('saldo'),

            'items' => $datos,

        ];
    }
}
