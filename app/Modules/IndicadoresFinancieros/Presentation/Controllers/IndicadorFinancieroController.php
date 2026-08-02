<?php

namespace App\Modules\IndicadoresFinancieros\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Responses\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\IndicadoresFinancieros\Application\Services\IndicadorFinancieroService;

class IndicadorFinancieroController extends Controller
{
    public function __construct(
        protected IndicadorFinancieroService $service
    ) {}

    public function index(Request $request)
    {
        try {

            if (!$request->user()->can('ver_indicadores_financieros')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            return ApiResponse::success(
                $this->service->obtenerIndicador(
                    $request->get('indicador')
                ),
                'Indicador financiero.'
            );

        } catch (HttpException $e) {

            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatusCode()
            );

        } catch (\Throwable $e) {

            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );

        }
    }
}