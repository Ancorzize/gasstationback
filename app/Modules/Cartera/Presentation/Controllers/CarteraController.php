<?php

namespace App\Modules\Cartera\Presentation\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Cartera\Application\Services\CarteraService;
use App\Modules\Cartera\Infrastructure\Mappers\CarteraMapper;
use App\Modules\Cartera\Presentation\Requests\StoreAbonoCarteraRequest;
use App\Modules\Cartera\Presentation\Resources\AbonoCarteraResource;
use App\Modules\Cartera\Presentation\Resources\MovimientoCarteraResource;

class CarteraController extends Controller
{
    public function __construct(
        protected CarteraService $carteraService
    ) {}

    public function resumen(Request $request)
    {
        try {
            if (!$request->user()->can('ver_reporte_cartera')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            return ApiResponse::success(
                $this->carteraService->resumen(),
                'Resumen de cartera.'
            );
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function movimientos(Request $request)
    {
        try {
            if (!$request->user()->can('ver_movimientos_cartera')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'cliente_id' => $request->get('cliente_id'),
                'tipo_movimiento' => $request->get('tipo_movimiento'),
                'medio_pago' => $request->get('medio_pago'),
                'fecha_desde' => $request->get('fecha_desde'),
                'fecha_hasta' => $request->get('fecha_hasta'),
                'search' => $request->get('search'),
            ];

            $movimientos = $this->carteraService->paginateMovimientos(
                $filters,
                (int) $request->get('per_page', 10)
            );

            return ApiResponse::success([
                'items' => MovimientoCarteraResource::collection($movimientos->items()),
                'pagination' => [
                    'current_page' => $movimientos->currentPage(),
                    'last_page' => $movimientos->lastPage(),
                    'per_page' => $movimientos->perPage(),
                    'total' => $movimientos->total(),
                ],
            ], 'Listado de movimientos de cartera.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function registrarAbono(StoreAbonoCarteraRequest $request)
    {
        try {
            if (!$request->user()->can('registrar_abonos_cartera')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = CarteraMapper::fromArrayToCreateAbonoDTO(
                $request->validated(),
                $request->user()->id
            );

            $abono = $this->carteraService->registrarAbono($dto);

            return ApiResponse::success(
                new AbonoCarteraResource($abono),
                'Abono registrado correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}