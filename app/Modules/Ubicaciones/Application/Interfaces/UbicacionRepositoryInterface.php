<?php

namespace App\Modules\Ubicaciones\Application\Interfaces;

use Illuminate\Support\Collection;

interface UbicacionRepositoryInterface
{
    public function getPaisesActivos(): Collection;

    public function getDepartamentosActivosByPais(int $paisId): Collection;

    public function getCiudadesActivasByDepartamento(int $departamentoId): Collection;
}