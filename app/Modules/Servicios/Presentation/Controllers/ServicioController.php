<?php

namespace App\Modules\Servicios\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Servicios\Application\Services\ServicioService;
use App\Modules\Servicios\Infrastructure\Mappers\ServicioMapper;
use App\Modules\Servicios\Presentation\Requests\StoreServicioRequest;
use App\Modules\Servicios\Presentation\Requests\UpdateServicioRequest;
use App\Modules\Servicios\Presentation\Requests\ChangeServicioStatusRequest;
use App\Modules\Servicios\Presentation\Resources\ServicioResource;

class ServicioController extends Controller
{
    public function __construct(
        protected ServicioService $servicioService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_servicios')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'is_active' => $request->get('is_active'),
            ];

            $servicios = $this->servicioService->paginate(
                $filters,
                (int) $request->get('per_page', 10)
            );

            return ApiResponse::success([
                'items' => ServicioResource::collection($servicios->items()),
                'pagination' => [
                    'current_page' => $servicios->currentPage(),
                    'last_page' => $servicios->lastPage(),
                    'per_page' => $servicios->perPage(),
                    'total' => $servicios->total(),
                ]
            ], 'Listado de servicios.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_servicios')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $servicio = $this->servicioService->findById($id);

            return ApiResponse::success(
                new ServicioResource($servicio),
                'Servicio encontrado.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StoreServicioRequest $request)
    {
        try {
            if (!$request->user()->can('crear_servicios')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = ServicioMapper::fromArrayToCreateDTO($request->validated());
            $servicio = $this->servicioService->create($dto);

            return ApiResponse::success(
                new ServicioResource($servicio),
                'Servicio creado correctamente.',
                201
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function update(UpdateServicioRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('editar_servicios')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = ServicioMapper::fromArrayToUpdateDTO($request->validated());
            $servicio = $this->servicioService->update($id, $dto);

            return ApiResponse::success(
                new ServicioResource($servicio),
                'Servicio actualizado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function changeStatus(ChangeServicioStatusRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('cambiar_estado_servicios')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $servicio = $this->servicioService->changeStatus(
                $id,
                (bool) $request->validated()['is_active']
            );

            return ApiResponse::success(
                new ServicioResource($servicio),
                'Estado del servicio actualizado correctamente.'
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
            if (!$request->user()->can('eliminar_servicios')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $this->servicioService->delete($id);

            return ApiResponse::success(
                null,
                'Servicio eliminado correctamente.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}