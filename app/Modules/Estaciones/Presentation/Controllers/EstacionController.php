<?php

namespace App\Modules\Estaciones\Presentation\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Shared\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Estaciones\Application\Services\EstacionService;
use App\Modules\Estaciones\Infrastructure\Mappers\EstacionMapper;
use App\Modules\Estaciones\Presentation\Requests\StoreEstacionRequest;
use App\Modules\Estaciones\Presentation\Requests\UpdateEstacionRequest;
use App\Modules\Estaciones\Presentation\Requests\ChangeEstacionStatusRequest;
use App\Modules\Estaciones\Presentation\Resources\EstacionResource;

class EstacionController extends Controller
{
    public function __construct(
        protected EstacionService $estacionService
    ) {}

    public function index(Request $request)
    {
        try {
            if (!$request->user()->can('ver_estaciones')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $filters = [
                'search' => $request->get('search'),
                'is_active' => $request->get('is_active'),
            ];

            $estaciones = $this->estacionService->paginate($filters, (int) $request->get('per_page', 10));

            return ApiResponse::success([
                'items' => EstacionResource::collection($estaciones->items()),
                'pagination' => [
                    'current_page' => $estaciones->currentPage(),
                    'last_page' => $estaciones->lastPage(),
                    'per_page' => $estaciones->perPage(),
                    'total' => $estaciones->total(),
                ],
            ], 'Listado de estaciones.');
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            if (!$request->user()->can('ver_estaciones')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            return ApiResponse::success(
                new EstacionResource($this->estacionService->findById($id)),
                'Estación encontrada.'
            );
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function store(StoreEstacionRequest $request)
    {
        try {
            if (!$request->user()->can('crear_estaciones')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = EstacionMapper::fromArrayToCreateDTO($request->validated());
            $estacion = $this->estacionService->create($dto);

            return ApiResponse::success(new EstacionResource($estacion), 'Estación creada correctamente.', 201);
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function update(UpdateEstacionRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('editar_estaciones')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $dto = EstacionMapper::fromArrayToUpdateDTO($request->validated());
            $estacion = $this->estacionService->update($id, $dto);

            return ApiResponse::success(new EstacionResource($estacion), 'Estación actualizada correctamente.');
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function changeStatus(ChangeEstacionStatusRequest $request, int $id)
    {
        try {
            if (!$request->user()->can('cambiar_estado_estaciones')) {
                return ApiResponse::error('Sin permisos.', 403);
            }

            $estacion = $this->estacionService->changeStatus(
                $id,
                (bool) $request->validated()['is_active']
            );

            return ApiResponse::success(new EstacionResource($estacion), 'Estado de la estación actualizado correctamente.');
        } catch (HttpException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}