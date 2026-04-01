<?php

namespace App\Modules\Ubicaciones\Infrastructure\Repositories;

use App\Models\Pais;
use App\Models\Ciudad;
use App\Models\Departamento;
use Illuminate\Support\Collection;
use App\Modules\Ubicaciones\Application\Interfaces\UbicacionRepositoryInterface;

class UbicacionRepository implements UbicacionRepositoryInterface
{
    public function getPaisesActivos(): Collection
    {
        return Pais::query()
            ->where('is_active', true)
            ->orderBy('nombre')
            ->get();
    }

    public function getDepartamentosActivosByPais(int $paisId): Collection
    {
        return Departamento::query()
            ->where('pais_id', $paisId)
            ->where('is_active', true)
            ->orderBy('nombre')
            ->get();
    }

    public function getCiudadesActivasByDepartamento(int $departamentoId): Collection
    {
        return Ciudad::query()
            ->where('departamento_id', $departamentoId)
            ->where('is_active', true)
            ->orderBy('nombre')
            ->get();
    }
}