<?php

namespace App\Modules\Ventas\Infrastructure\Repositories;

use App\Models\Caja;
use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Inventario;
use App\Models\PagoVenta;
use App\Models\DetalleVenta;
use App\Models\MovimientoCaja;
use App\Models\MovimientoCartera;
use App\Models\MovimientoInventario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\Ventas\Application\Interfaces\VentaRepositoryInterface;
use App\Models\TurnoIslero;
use App\Models\Manguera;
use App\Models\LecturaManguera;
use App\Models\User;
class VentaRepository implements VentaRepositoryInterface
{
    public function getAll(array $filters = [])
    {
        $query = Venta::query()
            ->with(['cliente', 'usuario', 'turnoIslero.estacion', 'detalles.producto', 'pagos']);
                
        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('numero_factura', 'like', "%{$search}%")
                    ->orWhereHas('cliente', function ($sub) use ($search) {
                        $sub->where('nombre', 'like', "%{$search}%")
                            ->orWhere('apellidos', 'like', "%{$search}%")
                            ->orWhere('documento', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($filters['cliente_id']) && $filters['cliente_id'] !== '') {
            $query->where('cliente_id', $filters['cliente_id']);
        }

        if (!empty($filters['tipo_venta'])) {
            $query->where('tipo_venta', $filters['tipo_venta']);
        }

        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (!empty($filters['estado_pago'])) {
            $query->where('estado_pago', $filters['estado_pago']);
        }

        if (!empty($filters['fecha_desde'])) {
            $query->whereDate('fecha_venta', '>=', $filters['fecha_desde']);
        }

        if (!empty($filters['fecha_hasta'])) {
            $query->whereDate('fecha_venta', '<=', $filters['fecha_hasta']);
        }

        return $query->orderByDesc('id')->get();
    }

    public function findById(int $id): ?Venta
    {
        return Venta::with([
            'cliente',
            'usuario',
            'turnoIslero.estacion',
            'usuarioAnulacion',
            'detalles.producto.marca',
            'detalles.producto.categoriaProducto',
            'detalles.producto.unidadMedida',
            'pagos.caja',
            'pagos.usuario',
            'detalles.manguera.bomba.estacion',
        ])->find($id);
    }

    public function createVenta(array $data): Venta
    {
        return Venta::create($data);
    }

    public function createDetalle(array $data): void
    {
        DetalleVenta::create($data);
    }

    public function createPago(array $data): PagoVenta
    {
        return PagoVenta::create($data)->load(['caja', 'usuario']);
    }

    public function findClienteById(int $id): ?Cliente
    {
        return Cliente::find($id);
    }

    public function updateCliente(Cliente $cliente, array $data): Cliente
    {
        $cliente->update($data);

        return $cliente->fresh();
    }

    public function findProductoById(int $id): ?Producto
    {
        return Producto::find($id);
    }

    public function findInventario(int $productoId, int $bodegaId): ?Inventario
    {
        return Inventario::query()
            ->where('producto_id', $productoId)
            ->where('bodega_id', $bodegaId)
            ->first();
    }

    public function decrementInventario(int $productoId, int $bodegaId, float $cantidad): void
    {
        Inventario::query()
            ->where('producto_id', $productoId)
            ->where('bodega_id', $bodegaId)
            ->decrement('cantidad', $cantidad);
    }

    public function createMovimientoInventario(array $data): MovimientoInventario
    {
        return MovimientoInventario::create($data);
    }

    public function createMovimientoCaja(array $data): MovimientoCaja
    {
        return MovimientoCaja::create($data)->load(['usuario', 'caja']);
    }

    public function createMovimientoCartera(array $data): MovimientoCartera
    {
        return MovimientoCartera::create($data)->load(['cliente', 'usuario']);
    }

    public function nextNumeroFactura(): string
    {
        $lastId = (int) Venta::query()->max('id');

        return str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
    }

    public function updateVenta(Venta $venta, array $data): Venta
    {
        $venta->update($data);

        return $venta->fresh();
    }

    public function incrementInventario(int $productoId, int $bodegaId, float $cantidad): void
    {
        Inventario::query()
            ->where('producto_id', $productoId)
            ->where('bodega_id', $bodegaId)
            ->increment('cantidad', $cantidad);
    }

    public function getTurnoAbiertoByUser(int $userId): ?TurnoIslero
    {
        return TurnoIslero::query()
            ->where('user_id', $userId)
            ->where('estado', 'abierto')
            ->first();
    }

    public function findMangueraById(int $id): ?Manguera
    {
        return Manguera::with([
            'bomba.estacion',
            'producto.marca',
            'producto.categoriaProducto',
            'producto.unidadMedida',
        ])->find($id);
    }

    public function getLecturaAbiertaByTurnoAndManguera(int $turnoId, int $mangueraId): ?LecturaManguera
    {
        return LecturaManguera::query()
            ->where('turno_islero_id', $turnoId)
            ->where('manguera_id', $mangueraId)
            ->whereNull('lectura_final')
            ->first();
    }

    public function findUserById(int $id): ?User
    {
        return User::with('bodega')->find($id);
    }

    public function getCajaAbiertaByTipoAndDestino(
        string $tipoCaja,
        int $destinoRecaudoId
    ): ?Caja
    {
        return Caja::query()
            ->where('estado', 'abierta')
            ->where('tipo_caja', $tipoCaja)
            ->where(
                'destino_recaudo_id',
                $destinoRecaudoId
            )
            ->first();
    }

    public function sumGalonesCombustibleByTurnoAndManguera(
        int $turnoId,
        int $mangueraId
    )
    {
        return DetalleVenta::query()

            ->join(
                'ventas',
                'ventas.id',
                '=',
                'detalle_ventas.venta_id'
            )

            ->where(
                'ventas.turno_islero_id',
                $turnoId
            )

            ->where(
                'detalle_ventas.manguera_id',
                $mangueraId
            )

            ->where(
                'ventas.estado',
                'confirmada'
            )

            ->sum('detalle_ventas.cantidad');
    }

    public function sumTotalCombustibleByTurnoAndManguera(
        int $turnoId,
        int $mangueraId
    )
    {
        return DetalleVenta::query()

            ->join(
                'ventas',
                'ventas.id',
                '=',
                'detalle_ventas.venta_id'
            )

            ->where(
                'ventas.turno_islero_id',
                $turnoId
            )

            ->where(
                'detalle_ventas.manguera_id',
                $mangueraId
            )

            ->where(
                'ventas.estado',
                'confirmada'
            )

            ->sum('detalle_ventas.total');
    }
}