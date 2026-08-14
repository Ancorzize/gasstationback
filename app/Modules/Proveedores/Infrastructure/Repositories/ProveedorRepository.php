<?php

namespace App\Modules\Proveedores\Infrastructure\Repositories;

use App\Models\Proveedor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\Proveedores\Application\Interfaces\ProveedorRepositoryInterface;

class ProveedorRepository implements ProveedorRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Proveedor::query();

        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(nombre) ILIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(nit) ILIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findById(int $id): ?Proveedor
    {
        return Proveedor::find($id);
    }

    public function create(array $data): Proveedor
    {
        return Proveedor::create($data);
    }

    public function update(Proveedor $proveedor, array $data): Proveedor
    {
        $proveedor->update($data);
        return $proveedor->fresh();
    }

    public function changeStatus(Proveedor $proveedor, bool $isActive): Proveedor
    {
        $proveedor->update(['is_active' => $isActive]);
        return $proveedor->fresh();
    }
}