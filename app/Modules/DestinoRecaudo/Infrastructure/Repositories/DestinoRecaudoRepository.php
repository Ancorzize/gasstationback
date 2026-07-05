<?php

namespace App\Modules\DestinoRecaudo\Infrastructure\Repositories;

use App\Models\DestinoRecaudo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\DestinoRecaudo\Application\Interfaces\DestinoRecaudoRepositoryInterface;

class DestinoRecaudoRepository implements DestinoRecaudoRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = DestinoRecaudo::query();

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {

                $q->where('codigo','like',"%{$filters['search']}%")
                  ->orWhere('nombre','like',"%{$filters['search']}%");

            });
        }

        if(isset($filters['is_active']) && $filters['is_active'] !== ''){
            $query->where(
                'is_active',
                filter_var(
                    $filters['is_active'],
                    FILTER_VALIDATE_BOOLEAN
                )
            );
        }

        return $query
            ->orderBy('nombre')
            ->paginate($perPage);
    }

    public function findById(int $id): ?DestinoRecaudo
    {
        return DestinoRecaudo::find($id);
    }

    public function create(array $data): DestinoRecaudo
    {
        return DestinoRecaudo::create($data);
    }

    public function update(
        DestinoRecaudo $destino,
        array $data
    ): DestinoRecaudo {

        $destino->update($data);

        return $destino->fresh();
    }

    public function changeStatus(
        DestinoRecaudo $destino,
        bool $isActive
    ): DestinoRecaudo {

        $destino->update([
            'is_active'=>$isActive
        ]);

        return $destino->fresh();
    }

    public function delete(
        DestinoRecaudo $destino
    ): void {

        $destino->delete();

    }
}