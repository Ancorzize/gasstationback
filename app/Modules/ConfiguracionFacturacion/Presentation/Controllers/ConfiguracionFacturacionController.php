<?php

namespace App\Modules\ConfiguracionFacturacion\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\ConfiguracionFacturacion\Application\Services\ConfiguracionFacturacionService;
use App\Modules\ConfiguracionFacturacion\Infrastructure\Mappers\ConfiguracionFacturacionMapper;
use App\Modules\ConfiguracionFacturacion\Presentation\Requests\UpdateConfiguracionFacturacionRequest;
use App\Modules\ConfiguracionFacturacion\Presentation\Resources\ConfiguracionFacturacionResource;

class ConfiguracionFacturacionController extends Controller
{
    public function __construct(
        protected ConfiguracionFacturacionService $configuracionFacturacionService
    ) {}

    public function show(Request $request)
    {
        try {
            $configuracion = $this->configuracionFacturacionService->get();

            return ApiResponse::success(
                new ConfiguracionFacturacionResource($configuracion),
                'Configuración de facturación electrónica.'
            );
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function update(UpdateConfiguracionFacturacionRequest $request)
    {
        try {
            $dto = ConfiguracionFacturacionMapper::fromArrayToUpdateDTO($request->validated());
            $configuracion = $this->configuracionFacturacionService->update($dto);

            return ApiResponse::success(
                new ConfiguracionFacturacionResource($configuracion),
                'Configuración de facturación electrónica actualizada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
