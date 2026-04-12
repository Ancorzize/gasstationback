<?php

namespace App\Modules\Inventarios\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Inventarios\Application\Services\InventarioService;
use App\Modules\Inventarios\Presentation\Resources\InventarioResource;

class InventarioController extends Controller
{
    public function __construct(
        protected InventarioService $inventarioService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_inventario')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'producto_id' => $request->get('producto_id'),
                'bodega_id' => $request->get('bodega_id'),
                'marca_id' => $request->get('marca_id'),
                'categoria_producto_id' => $request->get('categoria_producto_id'),
            ];

            $inventarios = $this->inventarioService->paginate(
                $filters,
                (int) $request->get('per_page', 10)
            );

            return ApiResponse::success([
                'items' => InventarioResource::collection($inventarios->items()),
                'pagination' => [
                    'current_page' => $inventarios->currentPage(),
                    'last_page' => $inventarios->lastPage(),
                    'per_page' => $inventarios->perPage(),
                    'total' => $inventarios->total(),
                ]
            ], 'Listado de inventario.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function porBodega(Request $request, int $bodegaId)
    {
        try {
            if (!$request->user()->can('ver_inventario')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $inventario = $this->inventarioService->getByBodega($bodegaId);

            return ApiResponse::success(
                InventarioResource::collection($inventario),
                'Inventario de la bodega.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function miBodega(Request $request)
    {
        try {
            if (
                !$request->user()->can('ver_mis_productos_bodega')
                && !$request->user()->can('ver_inventario')
            ) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $inventario = $this->inventarioService->getMiInventario($request->user());

            return ApiResponse::success(
                InventarioResource::collection($inventario),
                'Inventario de mi bodega.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function resumen(Request $request)
    {
        try {
            if (!$request->user()->can('ver_inventario')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
            ];

            $resumen = $this->inventarioService->getResumen($filters);

            return ApiResponse::success(
                $resumen,
                'Resumen de inventario.'
            );
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}