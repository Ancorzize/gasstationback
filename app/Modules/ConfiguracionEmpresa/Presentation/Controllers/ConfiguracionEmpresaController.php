<?php

namespace App\Modules\ConfiguracionEmpresa\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\ConfiguracionEmpresa\Application\Services\ConfiguracionEmpresaService;
use App\Modules\ConfiguracionEmpresa\Infrastructure\Mappers\ConfiguracionEmpresaMapper;
use App\Modules\ConfiguracionEmpresa\Presentation\Requests\UpdateConfiguracionEmpresaRequest;
use App\Modules\ConfiguracionEmpresa\Presentation\Resources\ConfiguracionEmpresaResource;

class ConfiguracionEmpresaController extends Controller
{
    public function __construct(
        protected ConfiguracionEmpresaService $configuracionEmpresaService
    ) {}

    public function show(Request $request)
    {
        try {
            $configuracion = $this->configuracionEmpresaService->get();

            return ApiResponse::success(
                $configuracion ? new ConfiguracionEmpresaResource($configuracion) : null,
                'Configuración de la empresa.'
            );
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function update(UpdateConfiguracionEmpresaRequest $request)
    {
        try {
            $dto = ConfiguracionEmpresaMapper::fromArrayToUpdateDTO($request->validated());
            $configuracion = $this->configuracionEmpresaService->update($dto);

            return ApiResponse::success(
                new ConfiguracionEmpresaResource($configuracion),
                'Configuración de la empresa actualizada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}