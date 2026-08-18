<?php

namespace App\Modules\MovimientosInventario\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\MovimientosInventario\Application\Services\MovimientoInventarioService;
use App\Modules\MovimientosInventario\Infrastructure\Mappers\MovimientoInventarioMapper;
use App\Modules\MovimientosInventario\Presentation\Requests\StoreMovimientoInventarioRequest;
use App\Modules\MovimientosInventario\Presentation\Resources\MovimientoInventarioResource;
use App\Modules\MovimientosInventario\Presentation\Requests\StoreMovimientoInventarioMasivoRequest;
class MovimientoInventarioController extends Controller
{
    public function __construct(
        protected MovimientoInventarioService $movimientoInventarioService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_movimientos_inventario')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'producto_id' => $request->get('producto_id'),
                'bodega_origen_id' => $request->get('bodega_origen_id'),
                'bodega_destino_id' => $request->get('bodega_destino_id'),
                'user_id' => $request->get('user_id'),
                'fecha_desde' => $request->get('fecha_desde'),
                'fecha_hasta' => $request->get('fecha_hasta'),

                'tipo_movimiento' => [
                    'entrada',
                    'traslado',
                ],
            ];

            $movimientos = $this->movimientoInventarioService
                ->getAll($filters);

            return ApiResponse::success([
                'items' => MovimientoInventarioResource::collection(
                    $movimientos
                ),
            ], 'Listado de movimientos de inventario.');

        } catch (\Throwable $e) {
            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );
        }
    }

    public function store(StoreMovimientoInventarioRequest $request)
    {
        try {
            if (!$request->user()->can('crear_movimientos_inventario')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = MovimientoInventarioMapper::fromArrayToCreateDTO(
                $request->validated(),
                $request->user()->id
            );

            $movimiento = $this->movimientoInventarioService->trasladar($dto);

            return ApiResponse::success(
                new MovimientoInventarioResource($movimiento),
                'Movimiento de inventario registrado correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function storeMasivo(
        StoreMovimientoInventarioMasivoRequest $request
    ) {
        try {

            if (!$request->user()->can('crear_movimientos_inventario')) {
                return ApiResponse::error(
                    'Sin permisos.',
                    403
                );
            }

            $dto =
                MovimientoInventarioMapper::fromArrayToMasivoDTO(
                    $request->validated(),
                    $request->user()->id
                );

            $movimientos =
                $this->movimientoInventarioService
                    ->trasladarMasivo($dto);

            return ApiResponse::success(
                [
                    'cantidad_movimientos' => count($movimientos),

                    'items' =>
                        MovimientoInventarioResource::collection(
                            $movimientos
                        ),
                ],
                'Movimiento masivo de inventario registrado correctamente.',
                201
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

    public function lotes(Request $request)
    {
        try {
            if (!$request->user()->can('ver_movimientos_inventario')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'fecha_desde' => $request->get('fecha_desde'),
                'fecha_hasta' => $request->get('fecha_hasta'),

                'tipo_movimiento' => [
                    'traslado',
                ],

                'bodega_origen_id' => $request->get('bodega_origen_id'),
                'bodega_destino_id' => $request->get('bodega_destino_id'),
                'user_id' => $request->get('user_id'),
            ];

            $lotes = $this->movimientoInventarioService
                ->getLotes($filters);

            return ApiResponse::success(
                $lotes,
                'Listado de lotes de movimientos de inventario.'
            );

        } catch (\Throwable $e) {
            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );
        }
    }

    public function productosLote(
        Request $request,
        string $codigoLote
    ) {
        try {
            if (!$request->user()->can('ver_movimientos_inventario')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $movimientos = $this->movimientoInventarioService
                ->getProductosByCodigoLote($codigoLote);

            if ($movimientos->isEmpty()) {
                return ApiResponse::error(
                    'El lote no existe o no tiene movimientos asociados.',
                    404
                );
            }

            return ApiResponse::success(
                MovimientoInventarioResource::collection(
                    $movimientos
                ),
                'Productos trasladados en el lote.'
            );

        } catch (\Throwable $e) {
            return ApiResponse::error(
                'Error interno del servidor.',
                500
            );
        }
    }
}