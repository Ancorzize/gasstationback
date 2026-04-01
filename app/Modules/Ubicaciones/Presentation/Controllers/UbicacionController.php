<?php

namespace App\Modules\Ubicaciones\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use App\Modules\Ubicaciones\Application\Services\UbicacionService;
use App\Modules\Ubicaciones\Presentation\Resources\PaisResource;
use App\Modules\Ubicaciones\Presentation\Resources\CiudadResource;
use App\Modules\Ubicaciones\Presentation\Resources\DepartamentoResource;

class UbicacionController extends Controller
{
    public function __construct(
        protected UbicacionService $ubicacionService
    ) {}

    public function paises(Request $request)
    {
        try {
            $paises = $this->ubicacionService->getPaisesActivos();

            return ApiResponse::success(
                PaisResource::collection($paises),
                'Listado de países.'
            );
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function departamentosPorPais(Request $request, int $paisId)
    {
        try {
            $departamentos = $this->ubicacionService->getDepartamentosActivosByPais($paisId);

            return ApiResponse::success(
                DepartamentoResource::collection($departamentos),
                'Listado de departamentos.'
            );
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function ciudadesPorDepartamento(Request $request, int $departamentoId)
    {
        try {
            $ciudades = $this->ubicacionService->getCiudadesActivasByDepartamento($departamentoId);

            return ApiResponse::success(
                CiudadResource::collection($ciudades),
                'Listado de ciudades.'
            );
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}