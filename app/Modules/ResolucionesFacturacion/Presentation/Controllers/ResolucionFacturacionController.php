<?php

namespace App\Modules\ResolucionesFacturacion\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\ResolucionesFacturacion\Application\Services\ResolucionFacturacionService;
use App\Modules\ResolucionesFacturacion\Infrastructure\Mappers\ResolucionFacturacionMapper;
use App\Modules\ResolucionesFacturacion\Presentation\Requests\StoreResolucionFacturacionRequest;
use App\Modules\ResolucionesFacturacion\Presentation\Requests\UpdateResolucionFacturacionRequest;
use App\Modules\ResolucionesFacturacion\Presentation\Resources\ResolucionFacturacionResource;

class ResolucionFacturacionController extends Controller
{
    public function __construct(
        protected ResolucionFacturacionService $resolucionService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_resoluciones_facturacion')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'tipo_documento' => $request->get('tipo_documento'),
                'proveedor' => $request->get('proveedor'),
                'ambiente' => $request->get('ambiente'),
                'is_active' => $request->get('is_active'),
            ];

            $resoluciones = $this->resolucionService->paginate(
                $filters,
                (int) $request->get('per_page', 1000)
            );

            return ApiResponse::success([
                'items' => ResolucionFacturacionResource::collection($resoluciones->items()),
                'pagination' => [
                    'current_page' => $resoluciones->currentPage(),
                    'last_page' => $resoluciones->lastPage(),
                    'per_page' => $resoluciones->perPage(),
                    'total' => $resoluciones->total(),
                ]
            ], 'Listado de resoluciones de facturación.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_resoluciones_facturacion')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $resolucion = $this->resolucionService->findById($id);

            return ApiResponse::success(
                new ResolucionFacturacionResource($resolucion),
                'Resolución de facturación encontrada.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StoreResolucionFacturacionRequest $request)
    {
        try {
            if (!$request->user()->can('crear_resoluciones_facturacion')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = ResolucionFacturacionMapper::fromArrayToCreateDTO($request->validated());
            $resolucion = $this->resolucionService->create($dto);

            return ApiResponse::success(
                new ResolucionFacturacionResource($resolucion),
                'Resolución de facturación creada correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function update(UpdateResolucionFacturacionRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('editar_resoluciones_facturacion')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = ResolucionFacturacionMapper::fromArrayToUpdateDTO($request->validated());
            $resolucion = $this->resolucionService->update($id, $dto);

            return ApiResponse::success(
                new ResolucionFacturacionResource($resolucion),
                'Resolución de facturación actualizada correctamente.'
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
            if (!$request->user()->can('eliminar_resoluciones_facturacion')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $this->resolucionService->delete($id);

            return ApiResponse::success(
                null,
                'Resolución de facturación eliminada correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
