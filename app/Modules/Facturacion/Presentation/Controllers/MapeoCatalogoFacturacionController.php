<?php

namespace App\Modules\Facturacion\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Facturacion\Application\Services\MapeoCatalogoFacturacionService;
use App\Modules\Facturacion\Infrastructure\Mappers\MapeoCatalogoFacturacionMapper;
use App\Modules\Facturacion\Presentation\Requests\StoreMapeoCatalogoFacturacionRequest;
use App\Modules\Facturacion\Presentation\Requests\UpdateMapeoCatalogoFacturacionRequest;
use App\Modules\Facturacion\Presentation\Resources\MapeoCatalogoFacturacionResource;

class MapeoCatalogoFacturacionController extends Controller
{
    public function __construct(
        protected MapeoCatalogoFacturacionService $mapeoService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_mapeos_catalogos')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'proveedor' => $request->get('proveedor'),
                'categoria' => $request->get('categoria'),
                'codigo_interno' => $request->get('codigo_interno'),
                'is_active' => $request->get('is_active'),
            ];

            $mapeos = $this->mapeoService->paginate(
                $filters,
                (int) $request->get('per_page', 1000)
            );

            return ApiResponse::success([
                'items' => MapeoCatalogoFacturacionResource::collection($mapeos->items()),
                'pagination' => [
                    'current_page' => $mapeos->currentPage(),
                    'last_page' => $mapeos->lastPage(),
                    'per_page' => $mapeos->perPage(),
                    'total' => $mapeos->total(),
                ]
            ], 'Listado de mapeos de catálogos.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_mapeos_catalogos')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $mapeo = $this->mapeoService->findById($id);

            return ApiResponse::success(
                new MapeoCatalogoFacturacionResource($mapeo),
                'Mapeo de catálogo encontrado.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StoreMapeoCatalogoFacturacionRequest $request)
    {
        try {
            if (!$request->user()->can('crear_mapeos_catalogos')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = MapeoCatalogoFacturacionMapper::fromArrayToCreateDTO($request->validated());
            $mapeo = $this->mapeoService->create($dto);

            return ApiResponse::success(
                new MapeoCatalogoFacturacionResource($mapeo),
                'Mapeo de catálogo creado correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function update(UpdateMapeoCatalogoFacturacionRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('editar_mapeos_catalogos')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = MapeoCatalogoFacturacionMapper::fromArrayToUpdateDTO($request->validated());
            $mapeo = $this->mapeoService->update($id, $dto);

            return ApiResponse::success(
                new MapeoCatalogoFacturacionResource($mapeo),
                'Mapeo de catálogo actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function destroy(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('eliminar_mapeos_catalogos')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $this->mapeoService->delete($id);

            return ApiResponse::success(
                null,
                'Mapeo de catálogo eliminado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
