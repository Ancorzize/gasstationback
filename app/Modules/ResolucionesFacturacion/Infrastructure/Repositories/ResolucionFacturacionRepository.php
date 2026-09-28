<?php

namespace App\Modules\ResolucionesFacturacion\Infrastructure\Repositories;

use App\Models\ResolucionFacturacion;
use App\Modules\ResolucionesFacturacion\Application\Interfaces\ResolucionFacturacionRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ResolucionFacturacionRepository implements ResolucionFacturacionRepositoryInterface
{
    public function findByTipoDocumento(string $tipoDocumento, ?int $empresaId = null): ?ResolucionFacturacion
    {
        $query = ResolucionFacturacion::query()
            ->where('tipo_documento', $tipoDocumento)
            ->where('is_active', true);

        if ($empresaId !== null) {
            $query->where(function ($q) use ($empresaId) {
                $q->where('configuracion_empresa_id', $empresaId)
                  ->orWhereNull('configuracion_empresa_id');
            });
        }

        return $query->latest('id')->first();
    }

    public function getAll(array $filters = []): Collection
    {
        $query = ResolucionFacturacion::query();

        if (!empty($filters['tipo_documento'])) {
            $query->where('tipo_documento', $filters['tipo_documento']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->latest('id')->get();
    }

    public function paginate(array $filters = [], int $perPage = 1000): LengthAwarePaginator
    {
        $query = ResolucionFacturacion::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('prefijo', 'like', "%{$search}%")
                  ->orWhere('numero_resolucion', 'like', "%{$search}%")
                  ->orWhere('tipo_documento', 'like', "%{$search}%")
                  ->orWhere('proveedor', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['tipo_documento'])) {
            $query->where('tipo_documento', strtolower(trim($filters['tipo_documento'])));
        }

        if (!empty($filters['proveedor'])) {
            $query->where('proveedor', strtolower(trim($filters['proveedor'])));
        }

        if (!empty($filters['ambiente'])) {
            $query->where('ambiente', strtolower(trim($filters['ambiente'])));
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findById(int $id): ?ResolucionFacturacion
    {
        return ResolucionFacturacion::find($id);
    }

    public function create(array $data): ResolucionFacturacion
    {
        return ResolucionFacturacion::create($data);
    }

    public function update(ResolucionFacturacion $resolucion, array $data): ResolucionFacturacion
    {
        $resolucion->update($data);
        return $resolucion->fresh();
    }

    public function delete(ResolucionFacturacion $resolucion): void
    {
        $resolucion->delete();
    }
}
