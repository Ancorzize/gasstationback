<?php

namespace App\Modules\PreciosCombustible\Presentation\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\PreciosCombustible\Application\Services\PrecioCombustibleService;
use App\Modules\PreciosCombustible\Infrastructure\Mappers\PrecioCombustibleMapper;
use App\Modules\PreciosCombustible\Presentation\Requests\StorePrecioCombustibleRequest;
use App\Modules\PreciosCombustible\Presentation\Requests\ChangePrecioCombustibleStatusRequest;
use App\Modules\PreciosCombustible\Presentation\Resources\PrecioCombustibleResource;

class PrecioCombustibleController extends Controller
{
    public function __construct(
        protected PrecioCombustibleService $precioService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_precios_combustible')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'producto_id' => $request->get('producto_id'),
                'is_active' => $request->get('is_active'),
            ];

            $precios = $this->precioService->paginate(
                $filters,
                (int) $request->get('per_page', 10)
            );

            return ApiResponse::success([
                'items' => PrecioCombustibleResource::collection($precios->items()),
                'pagination' => [
                    'current_page' => $precios->currentPage(),
                    'last_page' => $precios->lastPage(),
                    'per_page' => $precios->perPage(),
                    'total' => $precios->total(),
                ],
            ], 'Listado de precios de combustible.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_precios_combustible')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            return ApiResponse::success(
                new PrecioCombustibleResource($this->precioService->findById($id)),
                'Precio de combustible encontrado.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StorePrecioCombustibleRequest $request)
    {
        try {
            if (!$request->user()->can('crear_precios_combustible')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = PrecioCombustibleMapper::fromArrayToCreateDTO($request->validated());
            $precio = $this->precioService->create($dto);

            return ApiResponse::success(
                new PrecioCombustibleResource($precio),
                'Precio de combustible creado correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function changeStatus(ChangePrecioCombustibleStatusRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('cambiar_estado_precios_combustible')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $precio = $this->precioService->changeStatus(
                $id,
                (bool) $request->validated()['is_active']
            );

            return ApiResponse::success(
                new PrecioCombustibleResource($precio),
                'Estado del precio actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}