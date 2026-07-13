<?php

namespace App\Modules\Caja\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Caja\Application\Services\CajaService;
use App\Modules\Caja\Infrastructure\Mappers\CajaMapper;
use App\Modules\Caja\Presentation\Requests\AbrirCajaRequest;
use App\Modules\Caja\Presentation\Requests\CerrarCajaRequest;
use App\Modules\Caja\Presentation\Resources\CajaResource;
use App\Modules\Caja\Presentation\Resources\MovimientoCajaResource;

class CajaController extends Controller
{
    public function __construct(
        protected CajaService $cajaService
    ) {}

    public function actual(Request $request)
    {
        try {
            if (!$request->user()->can('ver_caja')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $cajas = $this->cajaService->getCajaActual();

            return ApiResponse::success(
                CajaResource::collection($cajas),
                'Cajas actuales.'
            );
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function abrir(AbrirCajaRequest $request)
    {
        try {
            if (!$request->user()->can('abrir_caja')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = CajaMapper::fromArrayToAperturaDTO(
                $request->validated(),
                $request->user()->id
            );

            $resultado = $this->cajaService->abrirCaja($dto);

            return ApiResponse::success(
                new CajaResource($resultado),
                'Caja abierta correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function cerrar(CerrarCajaRequest $request)
    {
        try {
            if (!$request->user()->can('cerrar_caja')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = CajaMapper::fromArrayToCierreDTO(
                $request->validated(),
                $request->user()->id
            );

            $resultado = $this->cajaService->cerrarCaja($dto);

            return ApiResponse::success(
                [
                    'success' => true
                ],
                'Cajas cerradas correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function movimientos(Request $request)
    {
        try {
            if (!$request->user()->can('ver_movimientos_caja')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'caja_id' => $request->get('caja_id'),
                'tipo_movimiento' => $request->get('tipo_movimiento'),
                'categoria_movimiento' => $request->get('categoria_movimiento'),
                'medio_pago' => $request->get('medio_pago'),
                'origen_modulo' => $request->get('origen_modulo'),
                'fecha_desde' => $request->get('fecha_desde'),
                'fecha_hasta' => $request->get('fecha_hasta'),
                'search' => $request->get('search'),
                'tipo_caja' => $request->get('tipo_caja'),
            ];

            $movimientos = $this->cajaService->paginateMovimientos(
                $filters,
                (int) $request->get('per_page', 10)
            );

            return ApiResponse::success([
                'items' => MovimientoCajaResource::collection($movimientos->items()),
                'pagination' => [
                    'current_page' => $movimientos->currentPage(),
                    'last_page' => $movimientos->lastPage(),
                    'per_page' => $movimientos->perPage(),
                    'total' => $movimientos->total()
                ]
            ], 'Listado de movimientos de caja.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function resumen(Request $request)
    {
        try {
            if (!$request->user()->can('ver_caja')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $resumen = $this->cajaService->getResumenCajaActual();

            return ApiResponse::success(
                $resumen,
                'Resumen de caja.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function historico(Request $request)
    {
        try {
            if (!$request->user()->can('ver_caja')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'tipo_caja' => $request->get('tipo_caja'),
                'estado' => $request->get('estado'),
                'fecha_desde' => $request->get('fecha_desde'),
                'fecha_hasta' => $request->get('fecha_hasta'),
                'user_apertura_id' => $request->get('user_apertura_id'),
                'user_cierre_id' => $request->get('user_cierre_id'),
                'search' => $request->get('search'),
            ];

            $cajas = $this->cajaService->paginateHistorico(
                $filters,
                (int) $request->get('per_page', 10)
            );

            return ApiResponse::success([
                'items' => CajaResource::collection($cajas->items()),
                'pagination' => [
                    'current_page' => $cajas->currentPage(),
                    'last_page' => $cajas->lastPage(),
                    'per_page' => $cajas->perPage(),
                    'total' => $cajas->total(),
                ]
            ], 'Histórico de cajas.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function sugerenciasApertura(Request $request)
    {
        try {

            if (!$request->user()->can('abrir_caja')) {
                return ApiResponse::error(
                    'Sin permisos.',
                    403
                );
            }

            return ApiResponse::success(
                CajaResource::collection(
                    $this->cajaService->getSugerenciasApertura()
                ),
                'Sugerencias de apertura.'
            );

        } catch (\Throwable $e) {

            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );

        }
    }
}