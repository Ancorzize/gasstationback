<?php

namespace App\Modules\Cartera\Infrastructure\Repositories;

use App\Models\Caja;
use App\Models\Cliente;
use App\Models\AbonoCartera;
use App\Models\MovimientoCaja;
use App\Models\MovimientoCartera;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\Cartera\Application\Interfaces\CarteraRepositoryInterface;
use App\Models\TurnoIslero;
use App\Models\Venta;
use App\Models\AbonoCarteraDetalle;
use App\Modules\Cartera\Application\DTOs\CreateAbonoCarteraDetalleDTO;
use App\Models\SaldoInicialCartera;
use App\Models\AplicacionAbonoSaldoInicial;
class CarteraRepository implements CarteraRepositoryInterface
{
    public function findClienteById(int $id): ?Cliente
    {
        return Cliente::find($id);
    }

    public function updateCliente(Cliente $cliente, array $data): Cliente
    {
        $cliente->update($data);

        return $cliente->fresh();
    }

    public function createAbono(array $data): AbonoCartera
    {
        return AbonoCartera::create($data)->load(['cliente', 'caja', 'usuario', 'turnoIslero.estacion']);
    }

    public function createMovimientoCartera(array $data): MovimientoCartera
    {
        return MovimientoCartera::create($data)->load(['cliente', 'usuario']);
    }

    public function paginateMovimientos(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = MovimientoCartera::query()
            ->with(['cliente', 'usuario']);

        if (isset($filters['cliente_id']) && $filters['cliente_id'] !== '') {
            $query->where('cliente_id', $filters['cliente_id']);
        }

        if (!empty($filters['tipo_movimiento'])) {
            $query->where('tipo_movimiento', $filters['tipo_movimiento']);
        }

        if (!empty($filters['medio_pago'])) {
            $query->where('medio_pago', $filters['medio_pago']);
        }

        if (!empty($filters['fecha_desde'])) {
            $query->whereDate('fecha_movimiento', '>=', $filters['fecha_desde']);
        }

        if (!empty($filters['fecha_hasta'])) {
            $query->whereDate('fecha_movimiento', '<=', $filters['fecha_hasta']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('descripcion', 'like', "%{$search}%")
                    ->orWhere('tipo_movimiento', 'like', "%{$search}%")
                    ->orWhere('medio_pago', 'like', "%{$search}%")
                    ->orWhereHas('cliente', function ($sub) use ($search) {
                        $sub->where('nombre', 'like', "%{$search}%")
                            ->orWhere('apellidos', 'like', "%{$search}%")
                            ->orWhere('documento', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function getMovimientosByCliente(int $clienteId): Collection
    {
        return MovimientoCartera::query()
            ->with(['usuario'])
            ->where('cliente_id', $clienteId)
            ->orderByDesc('fecha_movimiento')
            ->orderByDesc('id')
            ->get();
    }

    public function createMovimientoCaja(array $data): MovimientoCaja
    {
        return MovimientoCaja::create($data)->load(['usuario', 'caja']);
    }

    public function resumen(): array
    {
        $totalCartera = (float) Cliente::query()
            ->where('maneja_credito', true)
            ->sum('saldo_credito');

        $clientesConDeuda = Cliente::query()
            ->where('maneja_credito', true)
            ->where('saldo_credito', '>', 0)
            ->count();

        $clientesAlDia = Cliente::query()
            ->where('maneja_credito', true)
            ->where('saldo_credito', '<=', 0)
            ->count();

        $clientesCredito = Cliente::query()
            ->where('maneja_credito', true)
            ->count();

        return [
            'total_cartera' => $totalCartera,
            'clientes_credito' => $clientesCredito,
            'clientes_con_deuda' => $clientesConDeuda,
            'clientes_al_dia' => $clientesAlDia,
        ];
    }

    public function getTurnoAbiertoByUser(int $userId): ?TurnoIslero
    {
        return TurnoIslero::query()
            ->where('user_id', $userId)
            ->where('estado', 'abierto')
            ->first();
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

    public function findCajaById(
        int $id
    ): ?Caja
    {
        return Caja::query()
            ->with('destinoRecaudo')
            ->find($id);
    }

    public function getVentasPendientesCliente(int $clienteId)
    {
        return Venta::query()

            ->where('cliente_id', $clienteId)

            ->where('estado', 'confirmada')

            ->where('saldo_pendiente', '>', 0)

            ->orderBy('fecha_venta')

            ->lockForUpdate()

            ->get();
    }

    public function updateVenta(Venta $venta, array $data): Venta
    {
        $venta->update($data);

        return $venta->fresh();
    }

    public function createAbonoDetalle(
        CreateAbonoCarteraDetalleDTO $dto
    ){
        return AbonoCarteraDetalle::create([

            'abono_cartera_id' => $dto->abono_cartera_id,

            'venta_id' => $dto->venta_id,

            'valor_aplicado' => $dto->valor_aplicado,

        ]);
    }

    public function createSaldoInicial(array $data): SaldoInicialCartera
    {
        return SaldoInicialCartera::create($data)
            ->load([
                'cliente',
                'usuario',
            ]);
    }

    public function getSaldosInicialesPendientesCliente(
        int $clienteId
    ) {
        return SaldoInicialCartera::query()
            ->where('cliente_id', $clienteId)
            ->whereIn('estado', [
                'pendiente',
                'parcial',
            ])
            ->where('saldo_pendiente', '>', 0)
            ->orderBy('fecha_documento')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    public function updateSaldoInicial(
        SaldoInicialCartera $saldoInicial,
        array $data
    ): SaldoInicialCartera
    {
        $saldoInicial->update($data);

        return $saldoInicial->fresh()
            ->load([
                'cliente',
                'usuario',
            ]);
    }

    public function createAplicacionSaldoInicial(
        array $data
    ): AplicacionAbonoSaldoInicial
    {
        return AplicacionAbonoSaldoInicial::create($data)
            ->load([
                'saldoInicial',
            ]);
    }
}