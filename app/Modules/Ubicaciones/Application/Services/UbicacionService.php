<?php

namespace App\Modules\Ubicaciones\Application\Services;

use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Ubicaciones\Application\Interfaces\UbicacionRepositoryInterface;

class UbicacionService
{
    public function __construct(
        protected UbicacionRepositoryInterface $ubicacionRepository
    ) {}

    public function getPaisesActivos()
    {
        return $this->ubicacionRepository->getPaisesActivos();
    }

    public function getDepartamentosActivosByPais(int $paisId)
    {
        $departamentos = $this->ubicacionRepository->getDepartamentosActivosByPais($paisId);

        if ($departamentos->isEmpty()) {
            return collect();
        }

        return $departamentos;
    }

    public function getCiudadesActivasByDepartamento(int $departamentoId)
    {
        $ciudades = $this->ubicacionRepository->getCiudadesActivasByDepartamento($departamentoId);

        if ($ciudades->isEmpty()) {
            return collect();
        }

        return $ciudades;
    }
}