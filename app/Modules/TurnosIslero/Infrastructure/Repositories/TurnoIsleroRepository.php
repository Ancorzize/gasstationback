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

    public function sumVentasPagadasByTurno(int $turnoId): float
    {
        return (float) Venta::query()
            ->where('turno_islero_id', $turnoId)
            ->where('estado', 'confirmada')
            ->sum('total_pagado');
    }

    public function sumVentasCreditoByTurno(int $turnoId): float
    {
        return (float) Venta::query()
            ->where('turno_islero_id', $turnoId)
            ->where('estado', 'confirmada')
            ->sum('saldo_pendiente');
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
}