<?php

namespace App\Modules\Compras\Infrastructure\Repositories;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\Inventario;
use App\Models\PagoCompra;
use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\DetalleCompra;
use App\Models\MovimientoInventario;
use App\Models\SaldoInicialProveedor;
use App\Models\AplicacionSaldoInicialProveedor;
use App\Models\AnticipoProveedor;
use App\Models\AplicacionAnticipoCompra;
use App\Models\Proveedor;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\Compras\Application\Interfaces\CompraRepositoryInterface;

class CompraRepository implements CompraRepositoryInterface
{
    public function getAll(array $filters = []): Collection
    {
        $query = Compra::query()
            ->with([
                'proveedor',
                'bodega',
                'usuario',
                'detalles.producto'
            ]);

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where(
                    'numero_documento',
                    'like',
                    "%{$search}%"
                )
                ->orWhereHas('proveedor', function ($sub) use ($search) {
                    $sub->where('nombre', 'like', "%{$search}%")
                        ->orWhere('nit', 'like', "%{$search}%");
                });
            });
        }

        if (
            isset($filters['proveedor_id']) &&
            $filters['proveedor_id'] !== ''
        ) {
            $query->where(
                'proveedor_id',
                $filters['proveedor_id']
            );
        }

        if (
            isset($filters['bodega_id']) &&
            $filters['bodega_id'] !== ''
        ) {
            $query->where(
                'bodega_id',
                $filters['bodega_id']
            );
        }

        if (!empty($filters['estado'])) {
            $query->where(
                'estado',
                $filters['estado']
            );
        }

        if (!empty($filters['estado_pago'])) {
            $query->where(
                'estado_pago',
                $filters['estado_pago']
            );
        }

        if (!empty($filters['tipo_pago'])) {
            $query->where(
                'tipo_pago',
                $filters['tipo_pago']
            );
        }

        if (!empty($filters['fecha_desde'])) {
            $query->whereDate(
                'fecha_compra',
                '>=',
                $filters['fecha_desde']
            );
        }

        if (!empty($filters['fecha_hasta'])) {
            $query->whereDate(
                'fecha_compra',
                '<=',
                $filters['fecha_hasta']
            );
        }

        return $query
            ->orderByDesc('id')
            ->get();
    }

    public function findById(int $id): ?Compra
    {
        return Compra::with([
            'proveedor',
            'bodega',
            'usuario',
            'detalles.producto.marca',
            'detalles.producto.categoriaProducto',
            'detalles.producto.unidadMedida',
            'pagos.usuario',
            'aplicacionesAnticipo.anticipo',
        ])->find($id);
    }

    public function create(array $data): Compra
    {
        return Compra::create($data);
    }

    public function update(Compra $compra, array $data): Compra
    {
        $compra->update($data);

        return $compra->fresh();
    }

    public function deleteDetallesByCompra(int $compraId): void
    {
        DetalleCompra::query()
            ->where('compra_id', $compraId)
            ->delete();
    }

    public function createDetalle(array $data): void
    {
        DetalleCompra::create($data);
    }

    public function findInventario(int $productoId, int $bodegaId): ?Inventario
    {
        return Inventario::query()
            ->where('producto_id', $productoId)
            ->where('bodega_id', $bodegaId)
            ->first();
    }

    public function createInventario(array $data): Inventario
    {
        return Inventario::create($data);
    }

    public function incrementInventario(int $productoId, int $bodegaId, float $cantidad): void
    {
        Inventario::query()
            ->where('producto_id', $productoId)
            ->where('bodega_id', $bodegaId)
            ->increment('cantidad', $cantidad);
    }

    public function createMovimientoInventario(array $data): MovimientoInventario
    {
        return MovimientoInventario::create($data);
    }

    public function updateProductoPrecioCompra(int $productoId, float $precioCompra): void
    {
        Producto::query()
            ->where('id', $productoId)
            ->update(['precio_compra' => $precioCompra]);
    }

    public function createPago(array $data): PagoCompra
    {
        return PagoCompra::create($data)->load('usuario');
    }

    public function getPagosByCompra(int $compraId): Collection
    {
        return PagoCompra::query()
            ->with('usuario')
            ->where('compra_id', $compraId)
            ->orderByDesc('id')
            ->get();
    }

    public function createMovimientoCaja(array $data): MovimientoCaja
    {
        return MovimientoCaja::create($data);
    }

    public function findCajaById(
        int $id
    ): ?Caja
    {
        return Caja::query()
            ->where('id', $id)
            ->first();
    }

    public function getComprasPendientesByProveedor(
        int $proveedorId
    ): Collection {
        return Compra::query()
            ->where('proveedor_id', $proveedorId)
            ->where('estado', 'confirmada')
            ->where('saldo_pendiente', '>', 0)
            ->orderBy('fecha_compra')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    public function getSaldosInicialesPendientesByProveedor(int $proveedorId): Collection
    {
        return SaldoInicialProveedor::query()
            ->with(['usuario', 'aplicaciones'])
            ->where('proveedor_id', $proveedorId)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->where('saldo_pendiente', '>', 0)
            ->orderBy('fecha_documento')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    public function createSaldoInicial(array $data): SaldoInicialProveedor
    {
        return SaldoInicialProveedor::create($data);
    }

    public function updateSaldoInicial(SaldoInicialProveedor $saldoInicial, array $data): SaldoInicialProveedor
    {
        $saldoInicial->update($data);
        return $saldoInicial->fresh();
    }

    public function createAplicacionSaldoInicial(array $data): AplicacionSaldoInicialProveedor
    {
        return AplicacionSaldoInicialProveedor::create($data);
    }

    public function getAplicacionesBySaldoInicial(int $saldoInicialId): Collection
    {
        return AplicacionSaldoInicialProveedor::query()
            ->with(['usuario', 'movimientoCaja'])
            ->where('saldo_inicial_id', $saldoInicialId)
            ->orderByDesc('id')
            ->get();
    }

    public function existeDocumentoProveedor(int $proveedorId, string $numeroDocumento): bool
    {
        $numero = trim($numeroDocumento);

        $existeEnSaldoInicial = SaldoInicialProveedor::query()
            ->where('proveedor_id', $proveedorId)
            ->where('numero_documento', $numero)
            ->where('estado', '!=', 'anulado')
            ->exists();

        if ($existeEnSaldoInicial) {
            return true;
        }

        return Compra::query()
            ->where('proveedor_id', $proveedorId)
            ->where('numero_documento', $numero)
            ->where('estado', 'confirmada')
            ->exists();
    }

    public function generarSiguienteConsecutivoSIP(): string
    {
        $ultimo = SaldoInicialProveedor::query()
            ->where('numero_documento', 'like', 'SIP-%')
            ->orderByDesc('id')
            ->first();

        $ultimoNumero = 0;
        if ($ultimo && preg_match('/^SIP-(\d+)$/', $ultimo->numero_documento, $matches)) {
            $ultimoNumero = (int) $matches[1];
        }

        $maxId = (int) (SaldoInicialProveedor::query()->max('id') ?? 0);
        $siguiente = max($ultimoNumero, $maxId) + 1;

        do {
            $consecutivo = 'SIP-' . str_pad((string) $siguiente, 6, '0', STR_PAD_LEFT);
            $siguiente++;
        } while (SaldoInicialProveedor::query()->where('numero_documento', $consecutivo)->exists());

        return $consecutivo;
    }

    public function getDeudaByProveedor(int $proveedorId): array
    {
        $compras = $this->getComprasPendientesByProveedor($proveedorId);
        $saldosIniciales = $this->getSaldosInicialesPendientesByProveedor($proveedorId);

        $totalDeudaCompras = round(
            $compras->sum(
                fn ($compra) => (float) $compra->saldo_pendiente
            ),
            2
        );

        $totalDeudaSaldosIniciales = round(
            $saldosIniciales->sum(
                fn ($si) => (float) $si->saldo_pendiente
            ),
            2
        );

        $totalDeuda = round($totalDeudaCompras + $totalDeudaSaldosIniciales, 2);

        $proveedor = Proveedor::find($proveedorId);
        $saldoAFavor = (float) ($proveedor?->saldo_anticipo ?? 0);
        $deudaNeta = max(0, round($totalDeuda - $saldoAFavor, 2));

        return [
            'proveedor_id' => $proveedorId,
            'total_deuda' => $totalDeuda,
            'deuda_compras' => $totalDeudaCompras,
            'deuda_saldos_iniciales' => $totalDeudaSaldosIniciales,
            'saldo_a_favor_disponible' => $saldoAFavor,
            'deuda_neta' => $deudaNeta,
            'total_compras_pendientes' => $compras->count(),
            'total_saldos_iniciales_pendientes' => $saldosIniciales->count(),
            'compras' => $compras,
            'saldos_iniciales' => $saldosIniciales,
        ];
    }

    public function createAnticipo(array $data): AnticipoProveedor
    {
        return AnticipoProveedor::create($data);
    }

    public function findAnticipoById(int $id): ?AnticipoProveedor
    {
        return AnticipoProveedor::find($id);
    }

    public function getAnticiposDisponiblesByProveedor(int $proveedorId): Collection
    {
        return AnticipoProveedor::query()
            ->where('proveedor_id', $proveedorId)
            ->where('estado', 'disponible')
            ->where('saldo_disponible', '>', 0)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();
    }

    public function updateAnticipo(AnticipoProveedor $anticipo, array $data): AnticipoProveedor
    {
        $anticipo->update($data);
        return $anticipo->fresh();
    }

    public function generarSiguienteNumeroAnticipo(): string
    {
        $ultimo = AnticipoProveedor::query()
            ->where('numero', 'like', 'ANT-%')
            ->orderByDesc('id')
            ->first();

        $ultimoNumero = 0;
        if ($ultimo && preg_match('/^ANT-(\d+)$/', $ultimo->numero, $matches)) {
            $ultimoNumero = (int) $matches[1];
        }

        $maxId = (int) (AnticipoProveedor::query()->max('id') ?? 0);
        $siguiente = max($ultimoNumero, $maxId) + 1;

        do {
            $numero = 'ANT-' . str_pad((string) $siguiente, 6, '0', STR_PAD_LEFT);
            $siguiente++;
        } while (AnticipoProveedor::query()->where('numero', $numero)->exists());

        return $numero;
    }

    public function createAplicacionAnticipo(array $data): AplicacionAnticipoCompra
    {
        return AplicacionAnticipoCompra::create($data);
    }

    public function getAplicacionesAnticipoByCompra(int $compraId): Collection
    {
        return AplicacionAnticipoCompra::query()
            ->with(['anticipo', 'usuario'])
            ->where('compra_id', $compraId)
            ->orderByDesc('id')
            ->get();
    }

    public function updateAplicacionAnticipo(AplicacionAnticipoCompra $app, array $data): AplicacionAnticipoCompra
    {
        $app->update($data);
        return $app->fresh();
    }
}
