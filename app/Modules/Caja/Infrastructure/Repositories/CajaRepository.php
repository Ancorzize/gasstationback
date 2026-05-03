<?php

namespace App\Modules\Caja\Infrastructure\Repositories;

use App\Models\Caja;
use App\Models\MovimientoCaja;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\Caja\Application\Interfaces\CajaRepositoryInterface;

class CajaRepository implements CajaRepositoryInterface
{
    public function getCajaAbierta(): ?Caja
    {
        return Caja::query()
            ->with(['usuarioApertura', 'usuarioCierre'])
            ->where('estado', 'abierta')
            ->first();
    }

    public function findById(int $id): ?Caja
    {
        return Caja::with(['usuarioApertura', 'usuarioCierre'])->find($id);
    }

    public function createCaja(array $data): Caja
    {
        return Caja::create($data)->load(['usuarioApertura', 'usuarioCierre']);
    }

    public function updateCaja(Caja $caja, array $data): Caja
    {
        $caja->update($data);

        return $caja->fresh()->load(['usuarioApertura', 'usuarioCierre']);
    }

    public function createMovimiento(array $data): MovimientoCaja
    {
        return MovimientoCaja::create($data)->load(['usuario']);
    }

    public function paginateMovimientos(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = MovimientoCaja::query()
            ->with(['usuario', 'caja']);

        if (isset($filters['caja_id']) && $filters['caja_id'] !== '') {
            $query->where('caja_id', $filters['caja_id']);
        }

        if (!empty($filters['tipo_movimiento'])) {
            $query->where('tipo_movimiento', $filters['tipo_movimiento']);
        }

        if (!empty($filters['categoria_movimiento'])) {
            $query->where('categoria_movimiento', $filters['categoria_movimiento']);
        }

        if (!empty($filters['medio_pago'])) {
            $query->where('medio_pago', $filters['medio_pago']);
        }

        if (!empty($filters['origen_modulo'])) {
            $query->where('origen_modulo', $filters['origen_modulo']);
        }

        if (!empty($filters['fecha_desde'])) {
            $query->whereDate('fecha_movimiento', '>=', $filters['fecha_desde']);
        }

        if (!empty($filters['fecha_hasta'])) {
            $query->whereDate('fecha_movimiento', '<=', $filters['fecha_hasta']);
        }

        if (!empty($filters['tipo_caja'])) {
            $query->whereHas('caja', function ($q) use ($filters) {
                $q->where('tipo_caja', $filters['tipo_caja']);
            });
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('descripcion', 'like', "%{$search}%")
                    ->orWhere('categoria_movimiento', 'like', "%{$search}%")
                    ->orWhere('tipo_movimiento', 'like', "%{$search}%")
                    ->orWhere('medio_pago', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function getMovimientosByCaja(int $cajaId): Collection
    {
        return MovimientoCaja::query()
            ->with(['usuario'])
            ->where('caja_id', $cajaId)
            ->orderByDesc('id')
            ->get();
    }

    public function sumMovimientosByTipoAndMedio(int $cajaId, string $tipoMovimiento, string $medioPago): float
    {
        return (float) MovimientoCaja::query()
            ->where('caja_id', $cajaId)
            ->where('tipo_movimiento', $tipoMovimiento)
            ->where('medio_pago', $medioPago)
            ->sum('monto');
    }

    public function getCajasAbiertas(): Collection
    {
        return Caja::query()
            ->with(['usuarioApertura', 'usuarioCierre'])
            ->where('estado', 'abierta')
            ->orderBy('tipo_caja')
            ->get();
    }

    public function getCajaAbiertaByTipo(string $tipoCaja): ?Caja
    {
        return Caja::query()
            ->with(['usuarioApertura', 'usuarioCierre'])
            ->where('estado', 'abierta')
            ->where('tipo_caja', $tipoCaja)
            ->first();
    }

    public function existsCajaAbierta(): bool
    {
        return Caja::query()
            ->where('estado', 'abierta')
            ->exists();
    }

    public function sumMovimientosByTipo(int $cajaId, string $tipoMovimiento): float
    {
        return (float) MovimientoCaja::query()
            ->where('caja_id', $cajaId)
            ->where('tipo_movimiento', $tipoMovimiento)
            ->sum('monto');
    }

    public function paginateHistorico(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Caja::query()
            ->with(['usuarioApertura', 'usuarioCierre']);

        if (!empty($filters['tipo_caja'])) {
            $query->where('tipo_caja', $filters['tipo_caja']);
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

        if (!empty($filters['user_apertura_id'])) {
            $query->where('user_apertura_id', $filters['user_apertura_id']);
        }

        if (!empty($filters['user_cierre_id'])) {
            $query->where('user_cierre_id', $filters['user_cierre_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('tipo_caja', 'like', "%{$search}%")
                    ->orWhere('estado', 'like', "%{$search}%")
                    ->orWhere('observacion_apertura', 'like', "%{$search}%")
                    ->orWhere('observacion_cierre', 'like', "%{$search}%")
                    ->orWhereHas('usuarioApertura', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('usuarioCierre', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderByDesc('fecha_apertura')->paginate($perPage);
    }
}