<?php

namespace App\Modules\Facturacion\Infrastructure\Repositories;

use App\Models\MapeoCatalogoFacturacion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Modules\Facturacion\Application\Interfaces\MapeoCatalogoFacturacionRepositoryInterface;

class MapeoCatalogoFacturacionRepository implements MapeoCatalogoFacturacionRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 1000): LengthAwarePaginator
    {
        $query = MapeoCatalogoFacturacion::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('codigo_interno', 'like', "%{$search}%")
                  ->orWhere('codigo_externo', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['proveedor'])) {
            $query->where('proveedor', strtolower(trim($filters['proveedor'])));
        }

        if (!empty($filters['categoria'])) {
            $query->where('categoria', strtolower(trim($filters['categoria'])));
        }

        if (!empty($filters['codigo_interno'])) {
            $query->where('codigo_interno', strtolower(trim($filters['codigo_interno'])));
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findById(int $id): ?MapeoCatalogoFacturacion
    {
        return MapeoCatalogoFacturacion::find($id);
    }

    public function findDuplicate(string $proveedor, string $categoria, string $codigoInterno, ?int $excludeId = null): ?MapeoCatalogoFacturacion
    {
        $query = MapeoCatalogoFacturacion::where('proveedor', strtolower(trim($proveedor)))
            ->where('categoria', strtolower(trim($categoria)))
            ->where('codigo_interno', strtolower(trim($codigoInterno)));

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    public function create(array $data): MapeoCatalogoFacturacion
    {
        return MapeoCatalogoFacturacion::create($data);
    }

    public function update(MapeoCatalogoFacturacion $mapeo, array $data): MapeoCatalogoFacturacion
    {
        $mapeo->update($data);
        return $mapeo->fresh();
    }

    public function delete(MapeoCatalogoFacturacion $mapeo): void
    {
        $mapeo->delete();
    }
}
