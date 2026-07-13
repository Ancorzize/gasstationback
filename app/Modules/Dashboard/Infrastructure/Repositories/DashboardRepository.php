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

    public function ventasHoy(
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

    public function comprasHoy(
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

    public function gastosHoy(
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

    public function ventasMes(
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

    public function comprasMes(
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

    public function gastosMes(
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

    public function clientesTotales(
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

    public function clientesNuevosHoy(
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

    public function ventasCreditoHoy(
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

    public function abonosHoy(
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

        return (float) $query->sum('monto');
    }

    public function saldoCartera(
        ?string $fechaDesde,
        ?string $fechaHasta
    ): float
    {
        return (float)

        Cliente::query()

            ->sum(
                'saldo_cartera'
            );
    }

    public function inventarioValorizado(): float
    {
        return (float)

        Inventario::query()

            ->selectRaw(
                'SUM(cantidad * costo_promedio) as total'
            )

            ->value(
                'total'
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

                'valor' => (float) $item->total,

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
                'pago_ventas.venta_id'
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

            ->where(
                'inventarios.cantidad',
                '<=',
                10
            )

            ->orderBy('inventarios.cantidad')

            ->limit(10)

            ->get([

                'productos.id',

                'productos.nombre',

                'inventarios.cantidad',

            ])

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
}
