<?php

namespace App\Modules\TurnosIslero\Infrastructure\Repositories;

use App\Models\Manguera;
use App\Models\TurnoIslero;
use App\Models\LecturaManguera;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\TurnosIslero\Application\Interfaces\TurnoIsleroRepositoryInterface;
use App\Models\Venta;
use App\Models\AbonoCartera;
use App\Models\PrecioCombustible;
use App\Models\PagoVenta;
use App\Models\DetalleVenta;
use App\Models\Caja;
use App\Models\MovimientoCaja;
class TurnoIsleroRepository implements TurnoIsleroRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = TurnoIslero::query()
            ->with(['estacion', 'usuario']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('estado', 'like', "%{$search}%")
                    ->orWhereHas('usuario', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('estacion', function ($sub) use ($search) {
                        $sub->where('nombre', 'like', "%{$search}%")
                            ->orWhere('codigo', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($filters['estacion_id']) && $filters['estacion_id'] !== '') {
            $query->where('estacion_id', $filters['estacion_id']);
        }

        if (isset($filters['user_id']) && $filters['user_id'] !== '') {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (!empty($filters['fecha_desde'])) {
            $query->whereDate('fecha_apertura', '>=', $filters['fecha_desde']);
        }

        if (!empty($filters['fecha_hasta'])) {
            $query->whereDate('fecha_apertura', '<=', $filters['fecha_hasta']);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findById(int $id): ?TurnoIslero
    {
        return TurnoIslero::with([
            'estacion',
            'usuario',
            'lecturas.manguera.bomba.estacion',
            'lecturas.manguera.producto.marca',
            'lecturas.manguera.producto.categoriaProducto',
            'lecturas.manguera.producto.unidadMedida',
        ])->find($id);
    }

    public function getTurnoAbiertoByUser(int $userId): ?TurnoIslero
    {
        return TurnoIslero::query()
            ->with(['estacion', 'usuario', 'lecturas.manguera.producto'])
            ->where('user_id', $userId)
            ->where('estado', 'abierto')
            ->first();
    }

    public function existsTurnoAbiertoByUser(int $userId): bool
    {
        return TurnoIslero::query()
            ->where('user_id', $userId)
            ->where('estado', 'abierto')
            ->exists();
    }

    public function createTurno(array $data): TurnoIslero
    {
        return TurnoIslero::create($data)->load(['estacion', 'usuario']);
    }

    public function updateTurno(TurnoIslero $turno, array $data): TurnoIslero
    {
        $turno->update($data);

        return $turno->fresh()->load(['estacion', 'usuario']);
    }

    public function getManguerasActivasByEstacion(int $estacionId): Collection
    {
        return Manguera::query()
            ->with(['bomba.estacion', 'producto'])
            ->where('is_active', true)
            ->whereHas('bomba', function ($q) use ($estacionId) {
                $q->where('estacion_id', $estacionId);
            })
            ->orderBy('codigo')
            ->get();
    }

    public function getUltimaLecturaCerradaByManguera(int $mangueraId): ?LecturaManguera
    {
        return LecturaManguera::query()
            ->where('manguera_id', $mangueraId)
            ->whereNotNull('lectura_final')
            ->orderByDesc('id')
            ->first();
    }

    public function createLectura(array $data): LecturaManguera
    {
        return LecturaManguera::create($data)->load(['manguera.producto', 'manguera.bomba']);
    }

    public function findLecturaByTurnoAndManguera(int $turnoId, int $mangueraId): ?LecturaManguera
    {
        return LecturaManguera::query()
            ->where('turno_islero_id', $turnoId)
            ->where('manguera_id', $mangueraId)
            ->first();
    }

    public function updateLectura(LecturaManguera $lectura, array $data): LecturaManguera
    {
        $lectura->update($data);

        return $lectura->fresh()->load(['manguera.producto', 'manguera.bomba']);
    }

    public function sumVentasCombustibleByTurno(int $turnoId): float
    {
        return (float) Venta::query()
            ->where('turno_islero_id', $turnoId)
            ->where('estado', 'confirmada')
            ->where('tipo_origen', 'combustible')
            ->sum('total');
    }

    public function sumVentasLubricantesByTurno(int $turnoId): float
    {
        return (float) Venta::query()
            ->where('turno_islero_id', $turnoId)
            ->where('estado', 'confirmada')
            ->where('tipo_origen', 'pos')
            ->sum('total');
    }

    public function sumAbonosByTurno(int $turnoId): float
    {
        return (float) AbonoCartera::query()
            ->where('turno_islero_id', $turnoId)
            ->where('estado', 'registrado')
            ->sum('valor');
    }

    public function getPrecioVigenteProducto(int $productoId): ?float
    {
        $precio = PrecioCombustible::query()
            ->where('producto_id', $productoId)
            ->where('is_active', true)
            ->where('fecha_inicio', '<=', now())
            ->where(function ($q) {
                $q->whereNull('fecha_fin')
                ->orWhere('fecha_fin', '>=', now());
            })
            ->orderByDesc('fecha_inicio')
            ->first();

        return $precio ? (float) $precio->precio : null;
    }

    public function getManguerasOcupadasEnTurnosAbiertos(int $estacionId): Collection
    {
        return Manguera::query()
            ->whereHas('bomba', function ($q) use ($estacionId) {
                $q->where('estacion_id', $estacionId);
            })
            ->whereHas('turnosIslero', function ($q) {
                $q->where('estado', 'abierto');
            })
            ->pluck('id');
    }

    public function getManguerasDisponiblesByEstacion(int $estacionId): Collection
    {
        $ocupadas = $this->getManguerasOcupadasEnTurnosAbiertos($estacionId);

        return Manguera::query()
            ->with(['bomba.estacion', 'producto.marca', 'producto.categoriaProducto', 'producto.unidadMedida'])
            ->where('is_active', true)
            ->whereHas('bomba', function ($q) use ($estacionId) {
                $q->where('estacion_id', $estacionId);
            })
            ->whereNotIn('id', $ocupadas)
            ->orderBy('codigo')
            ->get();
    }

    public function getManguerasByIds(array $ids): Collection
    {
        return Manguera::query()
            ->with(['bomba.estacion', 'producto.marca', 'producto.categoriaProducto', 'producto.unidadMedida'])
            ->whereIn('id', $ids)
            ->get();
    }

    public function asignarMangueras(TurnoIslero $turno, array $mangueraIds): void
    {
        $turno->mangueras()->sync($mangueraIds);
    }

    public function sumVentasCreditoByTurno(int $turnoId): float
    {
        return (float) Venta::query()
            ->where('turno_islero_id', $turnoId)
            ->where('estado', 'confirmada')
            ->sum('saldo_pendiente');
    }

    public function sumPagosVentasByTurnoAndMetodo(int $turnoId, string $metodoPago): float
    {
        return (float) PagoVenta::query()
            ->whereHas('venta', function ($q) use ($turnoId) {
                $q->where('turno_islero_id', $turnoId)
                    ->where('estado', 'confirmada');
            })
            ->where('metodo_pago', $metodoPago)
            ->sum('monto');
    }

    public function sumAbonosByTurnoAndMetodo(int $turnoId, string $metodoPago): float
    {
        return (float) AbonoCartera::query()
            ->where('turno_islero_id', $turnoId)
            ->where('estado', 'registrado')
            ->where('medio_pago', $metodoPago)
            ->sum('valor');
    }

    public function sumGalonesCombustibleByTurnoAndManguera(int $turnoId, int $mangueraId): float
    {
        return (float) DetalleVenta::query()
            ->where('manguera_id', $mangueraId)
            ->whereHas('venta', function ($q) use ($turnoId) {
                $q->where('turno_islero_id', $turnoId)
                    ->where('tipo_origen', 'combustible')
                    ->where('estado', 'confirmada');
            })
            ->sum('cantidad');
    }

    public function sumTotalCombustibleByTurnoAndManguera(int $turnoId, int $mangueraId): float
    {
        return (float) DetalleVenta::query()
            ->where('manguera_id', $mangueraId)
            ->whereHas('venta', function ($q) use ($turnoId) {
                $q->where('turno_islero_id', $turnoId)
                    ->where('tipo_origen', 'combustible')
                    ->where('estado', 'confirmada');
            })
            ->sum('total');
    }

    public function getVentasLubricantesDetalleByTurno(int $turnoId): Collection
    {
        return DetalleVenta::query()
            ->with('producto')
            ->whereHas('venta', function ($q) use ($turnoId) {
                $q->where('turno_islero_id', $turnoId)
                    ->where('estado', 'confirmada')
                    ->where('tipo_origen', 'pos');
            })
            ->get()
            ->map(function ($detalle) {
                return [
                    'id' => $detalle->producto_id,
                    'nombre' => $detalle->producto?->nombre,
                    'cantidad' => (float) $detalle->cantidad,
                    'precio_unitario' => (float) $detalle->precio_unitario,
                    'total' => (float) $detalle->total,
                ];
            });
    }

    public function getAbonosDetalleByTurno(int $turnoId): Collection
    {
        return AbonoCartera::query()
            ->with('cliente')
            ->where('turno_islero_id', $turnoId)
            ->where('estado', 'registrado')
            ->get()
            ->map(function ($abono) {
                return [
                    'id' => $abono->id,
                    'cliente' => $abono->cliente?->nombre,
                    'monto' => (float) $abono->valor,
                    'fecha' => $abono->created_at,
                ];
            });
    }

    public function getResumenPagosPorDestino(
        int $turnoId
    ): Collection {

        return \App\Models\PagoVenta::query()

            ->join(
                'ventas',
                'ventas.id',
                '=',
                'pagos_venta.venta_id'
            )

            ->join(
                'detalle_ventas',
                'detalle_ventas.venta_id',
                '=',
                'ventas.id'
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
                'ventas.turno_islero_id',
                $turnoId
            )

            ->selectRaw('

                destinos_recaudo.id as destino_recaudo_id,

                destinos_recaudo.codigo,

                destinos_recaudo.nombre,

                pagos_venta.metodo_pago,

                SUM(detalle_ventas.total) total

            ')

            ->groupBy(

                'destinos_recaudo.id',

                'destinos_recaudo.codigo',

                'destinos_recaudo.nombre',

                'pagos_venta.metodo_pago'

            )

            ->get();
    }

    public function getDestinosRecaudoConVentas(
        int $turnoId
    ): Collection {

        return \App\Models\DetalleVenta::query()

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
                'ventas.turno_islero_id',
                $turnoId
            )

            ->select(

                'destinos_recaudo.id',

                'destinos_recaudo.codigo',

                'destinos_recaudo.nombre'

            )

            ->distinct()

            ->get();
    }

    public function getVentasDelTurnoConDestino(int $turnoId): Collection
    {
        return Venta::query()
            ->with([
                'pagos',
                'detalles.producto.categoriaProducto.destinoRecaudo',
            ])
            ->where('turno_islero_id', $turnoId)
            ->where('estado', 'confirmada')
            ->get();
    }

    
    public function getCajaAbiertaByTipoAndDestino(
        string $tipoCaja,
        int $destinoRecaudoId
    ): ?Caja
    {
        return Caja::query()
            ->where('estado','abierta')
            ->where('tipo_caja',$tipoCaja)
            ->where('destino_recaudo_id',$destinoRecaudoId)
            ->first();
    }

    public function createMovimientoCaja(
        array $data
    ): MovimientoCaja
    {
        return MovimientoCaja::create($data);
    }

    public function getResumenDestinosTurno(int $turnoId): Collection
    {
        return DetalleVenta::query()
            ->selectRaw("
                categorias_producto.destino_recaudo_id,
                destinos_recaudo.codigo,
                destinos_recaudo.nombre,
                pagos_venta.metodo_pago,
                SUM(detalle_ventas.total) as total
            ")
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->join('categorias_producto', 'categorias_producto.id', '=', 'productos.categoria_producto_id')
            ->join('destinos_recaudo', 'destinos_recaudo.id', '=', 'categorias_producto.destino_recaudo_id')
            ->join('pagos_venta', 'pagos_venta.venta_id', '=', 'ventas.id')
            ->where('ventas.turno_islero_id', $turnoId)
            ->where('ventas.estado', 'confirmada')
            ->groupBy(
                'categorias_producto.destino_recaudo_id',
                'destinos_recaudo.codigo',
                'destinos_recaudo.nombre',
                'pagos_venta.metodo_pago'
            )
            ->get();
    }

    public function getDestinosConCajaAbierta()
    {
        return Caja::query()

            ->join(
                'destinos_recaudo',
                'destinos_recaudo.id',
                '=',
                'cajas.destino_recaudo_id'
            )

            ->where(
                'cajas.estado',
                'abierta'
            )

            ->select(

                'destinos_recaudo.id',

                'destinos_recaudo.codigo',

                'destinos_recaudo.nombre'

            )

            ->distinct()

            ->orderBy('destinos_recaudo.nombre')

            ->get();
    }
}