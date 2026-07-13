<?php

namespace App\Modules\Gastos\Infrastructure\Repositories;

use App\Models\Caja;
use App\Models\Gasto;
use App\Models\CategoriaGasto;
use App\Models\MovimientoCaja;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\Gastos\Application\Interfaces\GastoRepositoryInterface;

class GastoRepository implements GastoRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Gasto::query()
            ->with(['proveedor', 'categoriaGasto', 'caja', 'usuario']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('descripcion', 'like', "%{$search}%")
                    ->orWhereHas('proveedor', function ($sub) use ($search) {
                        $sub->where('nombre', 'like', "%{$search}%")
                            ->orWhere('nit', 'like', "%{$search}%");
                    })
                    ->orWhereHas('categoriaGasto', function ($sub) use ($search) {
                        $sub->where('nombre', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($filters['proveedor_id']) && $filters['proveedor_id'] !== '') {
            $query->where('proveedor_id', $filters['proveedor_id']);
        }

        if (isset($filters['categoria_gasto_id']) && $filters['categoria_gasto_id'] !== '') {
            $query->where('categoria_gasto_id', $filters['categoria_gasto_id']);
        }

        if (!empty($filters['medio_pago'])) {
            $query->where('medio_pago', $filters['medio_pago']);
        }

        if (!empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (!empty($filters['fecha_desde'])) {
            $query->whereDate('fecha_gasto', '>=', $filters['fecha_desde']);
        }

        if (!empty($filters['fecha_hasta'])) {
            $query->whereDate('fecha_gasto', '<=', $filters['fecha_hasta']);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findById(int $id): ?Gasto
    {
        return Gasto::with([
                'proveedor',
                'categoriaGasto',
                'caja',
                'usuario',
                'usuarioAnulacion',
            ])->find($id);
    }

    public function create(array $data): Gasto
    {
        return Gasto::create($data)->load(['proveedor', 'categoriaGasto', 'caja', 'usuario']);
    }

    public function findCategoriaGastoById(int $id): ?CategoriaGasto
    {
        return CategoriaGasto::find($id);
    }

    public function createMovimientoCaja(array $data): MovimientoCaja
    {
        return MovimientoCaja::create($data);
    }

    public function getSaldoEfectivoCaja(int $cajaId): float
    {
        $ingresos = MovimientoCaja::query()
            ->where('caja_id', $cajaId)
            ->where('tipo_movimiento', 'ingreso')
            ->where('medio_pago', 'efectivo')
            ->sum('monto');

        $egresos = MovimientoCaja::query()
            ->where('caja_id', $cajaId)
            ->where('tipo_movimiento', 'egreso')
            ->where('medio_pago', 'efectivo')
            ->sum('monto');

        return (float) $ingresos - (float) $egresos;
    }

    public function update(Gasto $gasto, array $data): Gasto
    {
        $gasto->update($data);

        return $gasto->fresh()->load([
            'proveedor',
            'categoriaGasto',
            'caja',
            'usuario',
        ]);
    }
}