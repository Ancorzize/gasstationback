<?php

namespace App\Modules\Mangueras\Infrastructure\Repositories;

use App\Models\Manguera;
use App\Models\LecturaManguera;
use App\Modules\Mangueras\Application\Interfaces\MangueraRepositoryInterface;

class MangueraRepository implements MangueraRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10)
    {
        $query = Manguera::query()
            ->with(['bomba.estacion', 'producto']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%")
                    ->orWhereHas('producto', function ($sub) use ($search) {
                        $sub->where('nombre', 'like', "%{$search}%")
                            ->orWhere('codigo', 'like', "%{$search}%");
                    })
                    ->orWhereHas('bomba', function ($sub) use ($search) {
                        $sub->where('nombre', 'like', "%{$search}%")
                            ->orWhere('codigo', 'like', "%{$search}%");
                    })
                    ->orWhereHas('bomba.estacion', function ($sub) use ($search) {
                        $sub->where('nombre', 'like', "%{$search}%")
                            ->orWhere('codigo', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($filters['bomba_id']) && $filters['bomba_id'] !== '') {
            $query->where('bomba_id', $filters['bomba_id']);
        }

        if (isset($filters['producto_id']) && $filters['producto_id'] !== '') {
            $query->where('producto_id', $filters['producto_id']);
        }

        if (isset($filters['estacion_id']) && $filters['estacion_id'] !== '') {
            $query->whereHas('bomba', function ($q) use ($filters) {
                $q->where('estacion_id', $filters['estacion_id']);
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findById(int $id): ?Manguera
    {
        return Manguera::with([
            'bomba.estacion',
            'producto.marca',
            'producto.categoriaProducto',
            'producto.unidadMedida',
        ])->find($id);
    }

    public function create(array $data): Manguera
    {
        return Manguera::create($data)->load(['bomba.estacion', 'producto']);
    }

    public function update(Manguera $manguera, array $data): Manguera
    {
        $manguera->update($data);

        return $manguera->fresh()->load(['bomba.estacion', 'producto']);
    }

    public function changeStatus(Manguera $manguera, bool $isActive): Manguera
    {
        $manguera->update(['is_active' => $isActive]);

        return $manguera->fresh()->load(['bomba.estacion', 'producto']);
    }

    public function paginateLecturas(
        array $filters = [],
        int $perPage = 10
    )
    {
        $query = LecturaManguera::query()

            ->with([
                'turno.usuario',
                'manguera.bomba.estacion',
                'manguera.producto'
            ]);

        if (!empty($filters['search'])) {

            $search = $filters['search'];

            $query->where(function ($q) use ($search) {

                $q->whereHas('manguera', function ($sub) use ($search) {

                    $sub->where('nombre','like',"%{$search}%")
                        ->orWhere('codigo','like',"%{$search}%");

                })

                ->orWhereHas('turno.usuario', function ($sub) use ($search) {

                    $sub->where('name','like',"%{$search}%");

                });

            });

        }

        if (!empty($filters['manguera_id'])) {

            $query->where('manguera_id',$filters['manguera_id']);

        }

        if (!empty($filters['producto_id'])) {

            $query->whereHas('manguera', function ($q) use ($filters){

                $q->where(
                    'producto_id',
                    $filters['producto_id']
                );

            });

        }

        if (!empty($filters['bomba_id'])) {

            $query->whereHas('manguera', function ($q) use ($filters){

                $q->where(
                    'bomba_id',
                    $filters['bomba_id']
                );

            });

        }

        if (!empty($filters['estacion_id'])) {

            $query->whereHas('manguera.bomba', function ($q) use ($filters){

                $q->where(
                    'estacion_id',
                    $filters['estacion_id']
                );

            });

        }

        if (!empty($filters['fecha_desde'])) {

            $query->whereHas('turno', function ($q) use ($filters){

                $q->whereDate(
                    'fecha_apertura',
                    '>=',
                    $filters['fecha_desde']
                );

            });

        }

        if (!empty($filters['fecha_hasta'])) {

            $query->whereHas('turno', function ($q) use ($filters){

                $q->whereDate(
                    'fecha_apertura',
                    '<=',
                    $filters['fecha_hasta']
                );

            });

        }

        if (!empty($filters['turno_id'])) {
            $query->where('turno_islero_id', $filters['turno_id']
            );
        }

        return $query
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}