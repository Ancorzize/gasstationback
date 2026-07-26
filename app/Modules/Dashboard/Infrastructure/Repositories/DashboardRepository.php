<?php

namespace App\Modules\Dashboard\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use App\Models\DashboardWidgetRole;
use App\Modules\Dashboard\Application\Interfaces\DashboardRepositoryInterface;
use App\Models\Venta;
use App\Models\Compra;
use App\Models\Gasto;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Caja;
use App\Models\TurnoIslero;
use App\Models\Inventario;
use App\Models\MovimientoCartera;
use App\Models\PagoVenta;
use App\Models\DetalleVenta;
use App\Models\MovimientoCaja;

class DashboardRepository implements DashboardRepositoryInterface
{
   public function getWidgetsByRole(
        int $roleId
    ): Collection {

        return DashboardWidgetRole::query()

            ->with('widget')

            ->where('role_id', $roleId)

            ->where('visible', true)

            ->whereHas('widget', function ($q) {

                $q->where('is_active', true);

            })

            ->orderBy('orden')

            ->get();
    }

    public function ventas(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): float
    {
        $query = Venta::query()

            ->where('estado', 'confirmada');

        if ($fechaDesde) {

            $query->whereDate(
                'fecha_venta',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'fecha_venta',
                '<=',
                $fechaHasta
            );

        }

        return (float) $query->sum('total');
    }

    public function compras(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): float
    {
        $query = Compra::query()

            ->where('estado', 'confirmada');

        if ($fechaDesde) {

            $query->whereDate(
                'fecha_compra',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'fecha_compra',
                '<=',
                $fechaHasta
            );

        }

        return (float) $query->sum('total');
    }

    public function gastos(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): float
    {
        $query = Gasto::query()

            ->where('estado', 'registrado');

        if ($fechaDesde) {

            $query->whereDate(
                'fecha_gasto',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'fecha_gasto',
                '<=',
                $fechaHasta
            );

        }

        return (float) $query->sum('valor');
    }

    public function clientesTotales(): int
    {
        return Cliente::count();
    }

    public function clientesNuevos(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): int
    {
        $query = Cliente::query();

        if ($fechaDesde) {

            $query->whereDate(
                'created_at',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'created_at',
                '<=',
                $fechaHasta
            );

        }

        return $query->count();
    }

    public function productosActivos(): int
    {
        return Producto::query()

            ->where('is_active', true)

            ->count();
    }

    public function productosBajoStock(): int
    {
        return Inventario::query()

            ->where('cantidad', '<=', 10)

            ->count();
    }

    public function cajasAbiertas(): int
    {
        return Caja::query()

            ->where('estado', 'abierta')

            ->count();
    }

    public function turnosAbiertos(): int
    {
        return TurnoIslero::query()

            ->where('estado', 'abierto')

            ->count();
    }

    public function ventasCredito(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): float
    {
        $query = Venta::query()

            ->where(
                'tipo_venta',
                'credito'
            )

            ->where(
                'estado',
                'confirmada'
            );

        if ($fechaDesde) {

            $query->whereDate(
                'fecha_venta',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'fecha_venta',
                '<=',
                $fechaHasta
            );

        }

        return (float) $query->sum('total');
    }

    public function abonos(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): float
    {
        $query = MovimientoCartera::query()

            ->where(
                'tipo_movimiento',
                'abono'
            );

        if ($fechaDesde) {

            $query->whereDate(
                'fecha_movimiento',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'fecha_movimiento',
                '<=',
                $fechaHasta
            );

        }

        return (float) $query->sum('valor');
    }

    public function saldoCartera(
    ): float
    {
        return (float)

        Cliente::query()

            ->sum(
                'saldo_credito'
            );
    }

    public function ventasUltimos30Dias(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): array
    {
        $query = Venta::query()

            ->selectRaw("
                DATE(fecha_venta) as fecha,
                SUM(total) as total
            ")

            ->where(
                'estado',
                'confirmada'
            );

        if ($fechaDesde) {

            $query->whereDate(
                'fecha_venta',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'fecha_venta',
                '<=',
                $fechaHasta
            );

        }

        return $query

            ->groupByRaw("DATE(fecha_venta)")

            ->orderByRaw("DATE(fecha_venta)")

            ->get()

            ->map(fn($item) => [

                'fecha' => $item->fecha,

                'valor' => (float) $item->total

            ])

            ->toArray();
    }

    public function ventasPorMedioPago(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): array
    {
        $query = PagoVenta::query()

            ->join(
                'ventas',
                'ventas.id',
                '=',
                'pagos_venta.venta_id'
            )

            ->where(
                'ventas.estado',
                'confirmada'
            )

            ->selectRaw("
                metodo_pago,
                SUM(monto) total
            ");

        if ($fechaDesde) {

            $query->whereDate(
                'ventas.fecha_venta',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'ventas.fecha_venta',
                '<=',
                $fechaHasta
            );

        }

        return $query

            ->groupBy('metodo_pago')

            ->get()

            ->map(fn($item) => [

                'nombre' => $item->metodo_pago,

                'valor' => (float) $item->total,

            ])

            ->toArray();
    }

    public function gastosPorCategoria(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): array
    {
        $query = Gasto::query()

            ->join(
                'categorias_gasto',
                'categorias_gasto.id',
                '=',
                'gastos.categoria_gasto_id'
            )

            ->where(
                'gastos.estado',
                'registrado'
            )

            ->selectRaw("
                categorias_gasto.nombre,
                SUM(valor) total
            ");

        if ($fechaDesde) {

            $query->whereDate(
                'gastos.fecha_gasto',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'gastos.fecha_gasto',
                '<=',
                $fechaHasta
            );

        }

        return $query

            ->groupBy(
                'categorias_gasto.nombre'
            )

            ->get()

            ->map(fn($item) => [

                'nombre' => $item->nombre,

                'valor' => (float) $item->total,

            ])

            ->toArray();
    }

    public function topProductos(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): array
    {
        $query = DetalleVenta::query()

            ->join(
                'ventas',
                'ventas.id',
                '=',
                'detalle_ventas.venta_id'
            )

            ->join(
                'productos',
                'productos.id',
                '=',
                'detalle_ventas.producto_id'
            )

            ->where(
                'ventas.estado',
                'confirmada'
            )

            ->selectRaw("
                productos.nombre,
                SUM(cantidad) cantidad
            ");

        if ($fechaDesde) {

            $query->whereDate(
                'ventas.fecha_venta',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'ventas.fecha_venta',
                '<=',
                $fechaHasta
            );

        }

        return $query

            ->groupBy(
                'productos.nombre'
            )

            ->orderByDesc('cantidad')

            ->limit(10)

            ->get()

            ->map(fn($item) => [

                'nombre' => $item->nombre,

                'valor' => (float) $item->cantidad,

            ])

            ->toArray();
    }

    public function ventasPorIslero(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): array
    {
        $query = Venta::query()

            ->join(
                'users',
                'users.id',
                '=',
                'ventas.user_id'
            )

            ->where(
                'ventas.estado',
                'confirmada'
            )

            ->selectRaw("
                users.id,
                users.name,
                SUM(ventas.total) total
            ");

        if ($fechaDesde) {

            $query->whereDate(
                'ventas.fecha_venta',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'ventas.fecha_venta',
                '<=',
                $fechaHasta
            );

        }

        return $query

            ->groupBy(
                'users.id',
                'users.name'
            )

            ->orderByDesc('total')

            ->get()

            ->map(fn($item) => [

                'id' => $item->id,

                'nombre' => $item->name,

                'total' => (float) $item->total,

            ])

            ->toArray();
    }

    public function galonesPorCombustible(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): array
    {
        $query = DetalleVenta::query()

            ->join(
                'productos',
                'productos.id',
                '=',
                'detalle_ventas.producto_id'
            )

            ->join(
                'ventas',
                'ventas.id',
                '=',
                'detalle_ventas.venta_id'
            )

            ->whereNotNull(
                'detalle_ventas.manguera_id'
            )

            ->where(
                'ventas.estado',
                'confirmada'
            )

            ->selectRaw("
                productos.nombre,
                SUM(detalle_ventas.cantidad) galones
            ");

        if ($fechaDesde) {

            $query->whereDate(
                'ventas.fecha_venta',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'ventas.fecha_venta',
                '<=',
                $fechaHasta
            );

        }

        return $query

            ->groupBy(
                'productos.nombre'
            )

            ->orderByDesc('galones')

            ->get()

            ->map(fn($item) => [

                'nombre' => $item->nombre,

                'galones' => (float) $item->galones,

            ])

            ->toArray();
    }

    public function estadoCajas(): array
    {
        return Caja::query()

            ->with('destinoRecaudo')

            ->where('estado', 'abierta')

            ->orderBy('tipo_caja')

            ->get()

            ->map(function ($caja) {

                $ingresos = (float) MovimientoCaja::query()
                    ->where('caja_id', $caja->id)
                    ->where('tipo_movimiento', 'ingreso')
                    ->sum('monto');

                $egresos = (float) MovimientoCaja::query()
                    ->where('caja_id', $caja->id)
                    ->where('tipo_movimiento', 'egreso')
                    ->sum('monto');

                return [

                    'id' => $caja->id,

                    'nombre' => $caja->nombre,

                    'tipo_caja' => $caja->tipo_caja,

                    'destino' => optional($caja->destinoRecaudo)->nombre,

                    'estado' => $caja->estado,

                    'saldo' => $ingresos - $egresos,

                ];

            })

            ->values()

            ->toArray();
    }

    public function productosCriticos(): array
    {
        return Inventario::query()

            ->join(
                'productos',
                'productos.id',
                '=',
                'inventarios.producto_id'
            )

            ->selectRaw("
                productos.id,
                productos.nombre,
                SUM(inventarios.cantidad) cantidad
            ")

            ->groupBy(
                'productos.id',
                'productos.nombre'
            )

            ->havingRaw('SUM(inventarios.cantidad) <= 10')

            ->orderBy('cantidad')

            ->limit(10)

            ->get()

            ->map(fn ($item) => [

                'id' => $item->id,

                'nombre' => $item->nombre,

                'cantidad' => (float) $item->cantidad,

            ])

            ->toArray();
    }

    public function turnosAbiertosDetalle(): array
    {
        return TurnoIslero::query()

            ->with([
                'usuario',
                'estacion'
            ])

            ->where(
                'estado',
                'abierto'
            )

            ->orderBy('fecha_apertura')

            ->get()

            ->map(function ($turno) {

                return [

                    'id' => $turno->id,

                    'usuario' => $turno->usuario?->name,

                    'estacion' => $turno->estacion?->nombre,

                    'fecha_apertura' => $turno->fecha_apertura,

                    'horas_abierto' => round(
                        $turno->fecha_apertura
                            ->diffInMinutes(now()) / 60,
                        1
                    ),

                ];

            })

            ->toArray();
    }

    public function ultimasVentas(
        ?string $fechaDesde,
        ?string $fechaHasta,
        int $limit = 10
    ): array
    {
        $query = Venta::query()

            ->with('cliente')

            ->where(
                'estado',
                'confirmada'
            );

        if ($fechaDesde) {

            $query->whereDate(
                'fecha_venta',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'fecha_venta',
                '<=',
                $fechaHasta
            );

        }

        return $query

            ->orderByDesc('fecha_venta')

            ->limit($limit)

            ->get()

            ->map(function ($venta) {

                return [

                    'id' => $venta->id,

                    'factura' => $venta->prefijo .
                        $venta->numero_factura,

                    'cliente' => optional($venta->cliente)->nombre
                        ?? 'Consumidor Final',

                    'total' => (float) $venta->total,

                    'fecha' => $venta->fecha_venta,

                ];

            })

            ->toArray();
    }

    public function ultimosGastos(
        ?string $fechaDesde,
        ?string $fechaHasta,
        int $limit = 10
    ): array
    {
        $query = Gasto::query()

            ->with([
                'categoriaGasto',
                'proveedor'
            ])

            ->where(
                'estado',
                'registrado'
            );

        if ($fechaDesde) {

            $query->whereDate(
                'fecha_gasto',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'fecha_gasto',
                '<=',
                $fechaHasta
            );

        }

        return $query

            ->orderByDesc(
                'fecha_gasto'
            )

            ->limit($limit)

            ->get()

            ->map(function ($gasto) {

                return [

                    'id' => $gasto->id,

                    'categoria' =>
                        optional($gasto->categoriaGasto)->nombre,

                    'proveedor' =>
                        optional($gasto->proveedor)->nombre,

                    'valor' => (float) $gasto->valor,

                    'fecha' => $gasto->fecha_gasto,

                ];

            })

            ->toArray();
    }

    public function ultimasCompras(
        ?string $fechaDesde,
        ?string $fechaHasta,
        int $limit = 10
    ): array
    {
        $query = Compra::query()

            ->with([
                'proveedor'
            ])

            ->where(
                'estado',
                'confirmada'
            );

        if ($fechaDesde) {

            $query->whereDate(
                'fecha_compra',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'fecha_compra',
                '<=',
                $fechaHasta
            );

        }

        return $query

            ->orderByDesc(
                'fecha_compra'
            )

            ->limit($limit)

            ->get()

            ->map(function ($compra) {

                return [

                    'id' => $compra->id,

                    'documento' =>
                        $compra->prefijo .
                        $compra->numero_factura,

                    'proveedor' =>
                        optional($compra->proveedor)->nombre,

                    'total' =>
                        (float) $compra->total,

                    'fecha' =>
                        $compra->fecha_compra,

                ];

            })

            ->toArray();
    }

    public function ventasPorDestinoRecaudo(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): array
    {
        $query = DetalleVenta::query()

            ->join(
                'ventas',
                'ventas.id',
                '=',
                'detalle_ventas.venta_id'
            )

            ->join(
                'productos',
                'productos.id',
                '=',
                'detalle_ventas.producto_id'
            )

            ->join(
                'categorias_producto',
                'categorias_producto.id',
                '=',
                'productos.categoria_producto_id'
            )

            ->join(
                'destinos_recaudo',
                'destinos_recaudo.id',
                '=',
                'categorias_producto.destino_recaudo_id'
            )

            ->where(
                'ventas.estado',
                'confirmada'
            )

            ->selectRaw("
                destinos_recaudo.id,
                destinos_recaudo.nombre,
                SUM(detalle_ventas.total) total
            ");

        if ($fechaDesde) {

            $query->whereDate(
                'ventas.fecha_venta',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'ventas.fecha_venta',
                '<=',
                $fechaHasta
            );

        }

        return $query

            ->groupBy(
                'destinos_recaudo.id',
                'destinos_recaudo.nombre'
            )

            ->orderByDesc('total')

            ->get()

            ->map(fn ($item) => [

                'id' => $item->id,

                'nombre' => $item->nombre,

                'total' => (float) $item->total,

            ])

            ->toArray();
    }

    public function topClientes(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): array
    {
        $query = Venta::query()

            ->join(
                'clientes',
                'clientes.id',
                '=',
                'ventas.cliente_id'
            )

            ->where(
                'ventas.estado',
                'confirmada'
            )

            ->selectRaw("
                clientes.id,
                clientes.nombre,
                SUM(ventas.total) total
            ");

        if ($fechaDesde) {

            $query->whereDate(
                'ventas.fecha_venta',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'ventas.fecha_venta',
                '<=',
                $fechaHasta
            );

        }

        return $query

            ->groupBy(
                'clientes.id',
                'clientes.nombre'
            )

            ->orderByDesc('total')

            ->limit(10)

            ->get()

            ->map(fn ($item) => [

                'id' => $item->id,

                'nombre' => $item->nombre,

                'total' => (float) $item->total,

            ])

            ->toArray();
    }

    public function topProveedores(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): array
    {
        $query = Compra::query()

            ->join(
                'proveedores',
                'proveedores.id',
                '=',
                'compras.proveedor_id'
            )

            ->where(
                'compras.estado',
                'confirmada'
            )

            ->selectRaw("
                proveedores.id,
                proveedores.nombre,
                SUM(compras.total) total
            ");

        if ($fechaDesde) {

            $query->whereDate(
                'compras.fecha_compra',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'compras.fecha_compra',
                '<=',
                $fechaHasta
            );

        }

        return $query

            ->groupBy(
                'proveedores.id',
                'proveedores.nombre'
            )

            ->orderByDesc('total')

            ->limit(10)

            ->get()

            ->map(fn ($item) => [

                'id' => $item->id,

                'nombre' => $item->nombre,

                'total' => (float) $item->total,

            ])

            ->toArray();
    }

    public function recaudoPorMedioPago(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): array
    {
        $query = PagoVenta::query()

            ->join(
                'ventas',
                'ventas.id',
                '=',
                'pagos_venta.venta_id'
            )

            ->where(
                'ventas.estado',
                'confirmada'
            )

            ->selectRaw("
                metodo_pago,
                SUM(monto) total
            ");

        if ($fechaDesde) {

            $query->whereDate(
                'ventas.fecha_venta',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'ventas.fecha_venta',
                '<=',
                $fechaHasta
            );

        }

        return $query

            ->groupBy('metodo_pago')

            ->orderByDesc('total')

            ->get()

            ->map(fn($item)=>[

                'nombre'=>$item->metodo_pago,

                'total'=>(float)$item->total

            ])

            ->toArray();
    }

    public function flujoCajaUltimos30Dias(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): array
    {
        $query = MovimientoCaja::query()

            ->selectRaw("
                DATE(fecha_movimiento) fecha,

                SUM(
                    CASE
                        WHEN tipo_movimiento='ingreso'
                        THEN monto
                        ELSE 0
                    END
                ) ingresos,

                SUM(
                    CASE
                        WHEN tipo_movimiento='egreso'
                        THEN monto
                        ELSE 0
                    END
                ) egresos
            ");

        if ($fechaDesde) {

            $query->whereDate(
                'fecha_movimiento',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'fecha_movimiento',
                '<=',
                $fechaHasta
            );

        }

        return $query

            ->groupByRaw("DATE(fecha_movimiento)")

            ->orderByRaw("DATE(fecha_movimiento)")

            ->get()

            ->map(fn($item)=>[

                'fecha'=>$item->fecha,

                'ingresos'=>(float)$item->ingresos,

                'egresos'=>(float)$item->egresos,

            ])

            ->toArray();
    }


    public function ingresosVsEgresos(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): array
    {
        $ingresos = MovimientoCaja::query()

            ->where(
                'tipo_movimiento',
                'ingreso'
            );

        if ($fechaDesde) {

            $ingresos->whereDate(
                'fecha_movimiento',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $ingresos->whereDate(
                'fecha_movimiento',
                '<=',
                $fechaHasta
            );

        }

        $egresos = MovimientoCaja::query()

            ->where(
                'tipo_movimiento',
                'egreso'
            );

        if ($fechaDesde) {

            $egresos->whereDate(
                'fecha_movimiento',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $egresos->whereDate(
                'fecha_movimiento',
                '<=',
                $fechaHasta
            );

        }

        return [

            [

                'nombre' => 'Ingresos',

                'total' => (float) $ingresos->sum('monto'),

            ],

            [

                'nombre' => 'Egresos',

                'total' => (float) $egresos->sum('monto'),

            ],

        ];
    }

    public function ticketPromedio(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): float
    {
        $query = Venta::query()
            ->where('estado', 'confirmada');

        if ($fechaDesde) {
            $query->whereDate('fecha_venta', '>=', $fechaDesde);
        }

        if ($fechaHasta) {
            $query->whereDate('fecha_venta', '<=', $fechaHasta);
        }

        $ventas = (float) $query->sum('total');

        $cantidad = (clone $query)->count();

        if ($cantidad == 0) {
            return 0;
        }

        return round($ventas / $cantidad, 2);
    }

    public function comparativoVentasPeriodo(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): array
    {
        if (!$fechaDesde || !$fechaHasta) {

            $fechaDesde = now()->startOfMonth()->toDateString();
            $fechaHasta = now()->toDateString();
        }

        $inicio = \Carbon\Carbon::parse($fechaDesde)->startOfDay();
        $fin = \Carbon\Carbon::parse($fechaHasta)->endOfDay();

        $dias = $inicio->diffInDays($fin) + 1;

        $inicioAnterior = $inicio->copy()
            ->subDays($dias)
            ->startOfDay();

        $finAnterior = $inicio->copy()
            ->subDay()
            ->endOfDay();

        $actual = (float) Venta::query()

            ->where('estado', 'confirmada')

            ->whereBetween('fecha_venta', [
                $inicio,
                $fin
            ])

            ->sum('total');

        $anterior = (float) Venta::query()

            ->where('estado', 'confirmada')

            ->whereBetween('fecha_venta', [
                $inicioAnterior,
                $finAnterior
            ])

            ->sum('total');

        $porcentaje = 0;

        if ($anterior > 0) {

            $porcentaje =
                (($actual - $anterior) / $anterior) * 100;
        }

        return [

            'actual' => $actual,

            'anterior' => $anterior,

            'porcentaje' => round($porcentaje, 2),

        ];
    }

    public function ventasPorHora(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): array
    {
        $query = Venta::query()

            ->where('estado', 'confirmada');

        if ($fechaDesde) {
            $query->whereDate(
                'fecha_venta',
                '>=',
                $fechaDesde
            );
        }

        if ($fechaHasta) {
            $query->whereDate(
                'fecha_venta',
                '<=',
                $fechaHasta
            );
        }

        return $query

            ->selectRaw("
                EXTRACT(HOUR FROM fecha_venta) hora,
                SUM(total) total
            ")

            ->groupByRaw("
                EXTRACT(HOUR FROM fecha_venta)
            ")

            ->orderByRaw("
                EXTRACT(HOUR FROM fecha_venta)
            ")

            ->get()

            ->map(fn($item)=>[

                'hora'=>(int)$item->hora,

                'total'=>(float)$item->total,

            ])

            ->toArray();
    }


    public function productosSinMovimiento(): array {

        return Producto::query()

            ->leftJoin(
                'detalle_ventas',
                'detalle_ventas.producto_id',
                '=',
                'productos.id'
            )

            ->select(
                'productos.id',
                'productos.nombre'
            )

            ->groupBy(
                'productos.id',
                'productos.nombre'
            )

            ->havingRaw(
                'COUNT(detalle_ventas.id)=0'
            )

            ->orderBy('productos.nombre')

            ->limit(20)

            ->get()

            ->map(fn($item)=>[

                'id'=>$item->id,

                'nombre'=>$item->nombre,

            ])

            ->toArray();
    }

    public function saldoPorCaja(): array
    {
        return Caja::query()

            ->where('estado', 'abierta')

            ->orderBy('tipo_caja')

            ->get()

            ->map(function ($caja) {

                $ingresos = (float) MovimientoCaja::query()

                    ->where('caja_id', $caja->id)

                    ->where('tipo_movimiento', 'ingreso')

                    ->sum('monto');

                $egresos = (float) MovimientoCaja::query()

                    ->where('caja_id', $caja->id)

                    ->where('tipo_movimiento', 'egreso')

                    ->sum('monto');

                return [

                    'id' => $caja->id,

                    'nombre' => $caja->nombre,

                    'saldo' => $ingresos - $egresos,

                ];

            })

            ->values()

            ->toArray();
    }

    public function recaudoPorCaja(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): array {

        $query = MovimientoCaja::query()

            ->join(
                'cajas',
                'cajas.id',
                '=',
                'movimientos_caja.caja_id'
            )

            ->where('movimientos_caja.tipo_movimiento', 'ingreso')

            ->where('cajas.estado', 'abierta');

        if ($fechaDesde) {

            $query->whereDate(
                'movimientos_caja.fecha_movimiento',
                '>=',
                $fechaDesde
            );

        }

        if ($fechaHasta) {

            $query->whereDate(
                'movimientos_caja.fecha_movimiento',
                '<=',
                $fechaHasta
            );

        }

        return $query

            ->selectRaw("
                cajas.id,
                cajas.nombre,
                SUM(movimientos_caja.monto) total
            ")

            ->groupBy(
                'cajas.id',
                'cajas.nombre'
            )

            ->orderByDesc('total')

            ->get()

            ->map(fn ($item) => [

                'id' => $item->id,

                'nombre' => $item->nombre,

                'total' => (float) $item->total,

            ])

            ->toArray();
    }

    public function clientesMayorDeuda(
    ): array {

        return Cliente::query()

            ->where(
                'saldo_credito',
                '>',
                0
            )

            ->orderByDesc(
                'saldo_credito'
            )

            ->limit(10)

            ->get()

            ->map(fn($item)=>[

                'id'=>$item->id,

                'nombre'=>$item->nombre,

                'saldo'=>(float)$item->saldo_credito,

            ])

            ->toArray();
    }

    public function carteraVencida(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): float
    {
        $query = Venta::query()

            ->where('estado', 'confirmada')

            ->where('saldo_pendiente', '>', 0)

            ->whereNotNull('fecha_vencimiento')

            ->whereDate(
                'fecha_vencimiento',
                '<',
                now()
            );

        if ($fechaDesde) {

            $query->whereDate(
                'fecha_venta',
                '>=',
                $fechaDesde
            );
        }

        if ($fechaHasta) {

            $query->whereDate(
                'fecha_venta',
                '<=',
                $fechaHasta
            );
        }

        return (float) $query->sum('saldo_pendiente');
    }
}
